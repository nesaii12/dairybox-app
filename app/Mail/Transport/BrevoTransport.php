<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    public function __construct(protected string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new RuntimeException('BREVO_API_KEY is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $from = $email->getFrom()[0] ?? new Address(
            (string) config('mail.from.address', 'hello@example.com'),
            (string) config('mail.from.name', config('app.name', 'DairyBox'))
        );

        $formatAddresses = static function (array $addresses): array {
            return array_values(array_map(static function (Address $addr): array {
                $item = ['email' => $addr->getAddress()];
                if ($addr->getName() !== '') {
                    $item['name'] = $addr->getName();
                }
                return $item;
            }, $addresses));
        };

        $payload = [
            'sender' => array_filter([
                'email' => $from->getAddress(),
                'name' => $from->getName() ?: config('app.name', 'DairyBox'),
            ]),
            'to' => $formatAddresses($email->getTo()),
            'subject' => (string) $email->getSubject(),
        ];

        if ($cc = $email->getCc()) {
            $payload['cc'] = $formatAddresses($cc);
        }

        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $formatAddresses($bcc);
        }

        if ($replyTo = $email->getReplyTo()) {
            $firstReplyTo = $replyTo[0];
            $payload['replyTo'] = array_filter([
                'email' => $firstReplyTo->getAddress(),
                'name' => $firstReplyTo->getName() ?: null,
            ]);
        }

        $htmlBody = $email->getHtmlBody();
        $textBody = $email->getTextBody();

        if (is_string($htmlBody) && $htmlBody !== '') {
            $payload['htmlContent'] = $htmlBody;
        }

        if (is_string($textBody) && $textBody !== '') {
            $payload['textContent'] = $textBody;
        } elseif (!isset($payload['htmlContent'])) {
            $payload['textContent'] = ' ';
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'api-key' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])
            ->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Brevo API email failed (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
