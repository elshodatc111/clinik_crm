<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Telegram Bot API'ga yupqa qatlam. Istisno tashlamaydi; token xato matniga chiqmaydi. */
class TelegramApi
{
    public function __construct(private string $token)
    {
    }

    public function call(string $method, array $params = [], int $timeout = 15): array
    {
        try {
            $res = Http::asJson()->timeout($timeout)->post("https://api.telegram.org/bot{$this->token}/{$method}", $params);
            $j = $res->json();

            return is_array($j) ? $j : ['ok' => false, 'description' => 'Javob noto\'g\'ri (HTTP '.$res->status().')'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => str_replace($this->token, '***', $e->getMessage())];
        }
    }

    public function getMe(): array
    {
        return $this->call('getMe');
    }

    public function send(int|string $chatId, string $text, array $extra = []): array
    {
        return $this->call('sendMessage', ['chat_id' => $chatId, 'text' => $text] + $extra);
    }

    public function setWebhook(string $url, string $secret): array
    {
        return $this->call('setWebhook', ['url' => $url, 'secret_token' => $secret, 'allowed_updates' => ['message'], 'drop_pending_updates' => true]);
    }

    public function deleteWebhook(): array
    {
        return $this->call('deleteWebhook', ['drop_pending_updates' => true]);
    }

    public function webhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }
}
