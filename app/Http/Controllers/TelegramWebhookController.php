<?php

namespace App\Http\Controllers;

use App\Services\TelegramOtp;
use Illuminate\Http\Request;

/** Telegram webhook. Faqat setWebhook'da berilgan maxfiy sarlavha bilan keladigan so'rovlar qabul qilinadi. */
class TelegramWebhookController extends Controller
{
    public function handle(Request $r)
    {
        $secret = (string) $r->header('X-Telegram-Bot-Api-Secret-Token');
        abort_unless($secret !== '' && hash_equals(TelegramOtp::webhookSecret(), $secret), 403);

        try {
            TelegramOtp::handleUpdate((array) $r->json()->all());
        } catch (\Throwable $e) {
            report($e); // Telegram qayta-qayta yubormasligi uchun baribir 200 qaytaramiz
        }

        return response()->json(['ok' => true]);
    }
}
