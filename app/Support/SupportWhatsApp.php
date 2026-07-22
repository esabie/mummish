<?php

namespace App\Support;

use App\Services\MnotifySmsService;

class SupportWhatsApp
{
    public static function chatUrl(?string $phone = null, ?string $prefilledMessage = null): string
    {
        $phone ??= (string) config('marketplace.support_phone', '0208062428');
        $digits = MnotifySmsService::normalizeToInternationalDigits($phone);

        if ($digits === '') {
            return 'https://wa.me/';
        }

        $url = 'https://wa.me/'.$digits;

        $message = trim((string) $prefilledMessage);
        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }
}
