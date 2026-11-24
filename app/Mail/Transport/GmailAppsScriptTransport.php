<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class GmailAppsScriptTransport extends AbstractTransport
{
    public function __construct(
        protected string $webhookUrl,
        protected string $secret = ''
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->webhookUrl) === '') {
            throw new RuntimeException('GMAIL_SCRIPT_URL is not configured.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $to = array_values(array_map(
            static fn (Address $addr): string => $addr->getAddress(),
            $email->getTo()
        ));

        $from = $email->getFrom()[0] ?? null;
        $senderName = ($from && $from->getName() !== '')
            ? $from->getName()
            : (string) config('mail.from.name', config('app.name', 'DairyBox'));

        $payload = [
            'secret' => $this->secret,
            'to' => implode(',', $to),
            'subject' => (string) $email->getSubject(),
            'htmlBody' => (string) ($email->getHtmlBody() ?? ''),
            'textBody' => (string) ($email->getTextBody() ?? ''),
            'name' => $senderName,
        ];

        $response = Http::timeout(20)
            ->asJson()
            ->post($this->webhookUrl, $payload);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Gmail Apps Script mail failed (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $json = $response->json();
        if (is_array($json) && array_key_exists('ok', $json) && !$json['ok']) {
            throw new RuntimeException('Gmail Apps Script mail rejected: ' . ($json['error'] ?? 'Unknown error'));
        }
    }

    public function __toString(): string
    {
        return 'gmail_script';
    }
}
