<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Mock sending a WhatsApp message by logging it.
     */
    public function sendMessage(string $phone, string $message): void
    {
        Log::info("WhatsApp message to {$phone}: {$message}");
    }
}
