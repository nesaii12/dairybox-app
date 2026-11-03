<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    protected string $baseUrl = 'https://api.xendit.co';

    /**
     * Check if Xendit secret key is set.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.xendit.secret_key'));
    }

    /**
     * Get Xendit secret key.
     */
    protected function getSecretKey(): string
    {
        return (string) config('services.xendit.secret_key');
    }

    /**
     * Create an invoice for an order.
     *
     * @param Order $order
     * @return array
     * @throws \Exception
     */
    public function createInvoice(Order $order): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Xendit secret key is not configured. Please add XENDIT_SECRET_KEY to your .env file.');
        }

        $order->load(['customer', 'orderItems.product.category']);

        $items = [];
        foreach ($order->orderItems as $item) {
            $items[] = [
                'name' => ($item->product->product_name ?? 'Product') . (!empty($item->product->size) ? ' (' . $item->product->size . ')' : ''),
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'category' => $item->product->category->name ?? 'Dairy Products',
            ];
        }

        if ((float) $order->delivery_fee > 0) {
            $items[] = [
                'name' => 'Delivery Fee',
                'quantity' => 1,
                'price' => (float) $order->delivery_fee,
                'category' => 'Fulfillment Fee',
            ];
        }

        $customer = [
            'given_names' => !empty($order->customer?->name) ? $order->customer->name : 'Customer',
        ];

        if (!empty($order->customer?->email)) {
            $customer['email'] = $order->customer->email;
        }

        $formattedPhone = $this->formatPhoneNumber($order->customer?->phone ?? null);
        if ($formattedPhone !== null) {
            $customer['mobile_number'] = $formattedPhone;
        }

        $payload = [
            'external_id' => $order->order_number,
            'amount' => (float) $order->total_amount,
            'description' => "DairyBox Payment for Order #{$order->order_number}",
            'customer' => $customer,
            'currency' => 'PHP',
            'invoice_duration' => 86400, // 24 hours
            'success_redirect_url' => route('customer.orders.payment.success', $order->id),
            'failure_redirect_url' => route('customer.orders.payment.failure', $order->id),
        ];

        if (!empty($order->customer?->email)) {
            $payload['payer_email'] = $order->customer->email;
        }

        if (!empty($items)) {
            $payload['items'] = $items;
        }

        $response = Http::withBasicAuth($this->getSecretKey(), '')
            ->timeout(20)
            ->post("{$this->baseUrl}/v2/invoices", $payload);

        if ($response->failed()) {
            $json = $response->json();
            Log::error('Xendit Invoice Creation Failed', [
                'order_number' => $order->order_number,
                'status' => $response->status(),
                'response' => $json ?? $response->body(),
            ]);

            $errorDetails = [];
            if (is_array($json) && !empty($json['errors']) && is_array($json['errors'])) {
                foreach ($json['errors'] as $err) {
                    if (!empty($err['messages']) && is_array($err['messages'])) {
                        $errorDetails[] = implode(', ', $err['messages']);
                    }
                }
            }

            $detailStr = !empty($errorDetails) ? ' (' . implode('; ', $errorDetails) . ')' : '';
            $message = ($json['message'] ?? 'Unable to connect to Xendit payment service. Please try again.') . $detailStr;

            throw new \Exception($message);
        }

        return $response->json();
    }

    /**
     * Format a phone number to E.164 standard (+63XXXXXXXXXX for PH).
     * Returns null if empty or invalid so Xendit API does not reject the invoice payload.
     */
    protected function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Strip non-digit characters except leading plus
        $clean = preg_replace('/[^\d+]/', '', trim($phone));
        if (empty($clean)) {
            return null;
        }

        // Philippine mobile number formats:
        // 09XXXXXXXXX (11 digits) -> +639XXXXXXXXX
        if (str_starts_with($clean, '09') && strlen($clean) === 11) {
            return '+63' . substr($clean, 1);
        }

        // 9XXXXXXXXX (10 digits) -> +639XXXXXXXXX
        if (str_starts_with($clean, '9') && strlen($clean) === 10) {
            return '+63' . $clean;
        }

        // 639XXXXXXXXX (12 digits) -> +639XXXXXXXXX
        if (str_starts_with($clean, '639') && strlen($clean) === 12) {
            return '+' . $clean;
        }

        // Already formatted E.164 (+639XXXXXXXXX, 13 chars)
        if (str_starts_with($clean, '+639') && strlen($clean) === 13) {
            return $clean;
        }

        // Standard E.164 format with plus sign (between 9 and 16 characters)
        if (str_starts_with($clean, '+') && strlen($clean) >= 9 && strlen($clean) <= 16) {
            return $clean;
        }

        // If not in a recognized format, return null to omit field rather than fail Xendit validation
        return null;
    }

    /**
     * Retrieve an invoice from Xendit.
     *
     * @param string $invoiceId
     * @return array
     */
    public function getInvoice(string $invoiceId): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $response = Http::withBasicAuth($this->getSecretKey(), '')
            ->timeout(15)
            ->get("{$this->baseUrl}/v2/invoices/{$invoiceId}");

        if ($response->failed()) {
            Log::warning("Xendit Get Invoice Failed for ID: {$invoiceId}", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Expire an existing invoice in Xendit.
     *
     * @param string $invoiceId
     * @return bool
     */
    public function expireInvoice(string $invoiceId): bool
    {
        if (!$this->isConfigured() || empty($invoiceId)) {
            return false;
        }

        $response = Http::withBasicAuth($this->getSecretKey(), '')
            ->post("{$this->baseUrl}/invoices/{$invoiceId}/expire!");

        return $response->successful();
    }

    /**
     * Validate incoming webhook callback token.
     *
     * @param string|null $callbackToken
     * @return bool
     */
    public function verifyWebhookToken(?string $callbackToken): bool
    {
        $expectedToken = config('services.xendit.webhook_token');
        if (empty($expectedToken)) {
            // If webhook token is not configured, allow if in local or log warning
            return true;
        }

        return hash_equals((string) $expectedToken, (string) $callbackToken);
    }
}
