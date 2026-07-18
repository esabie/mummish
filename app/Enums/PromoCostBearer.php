<?php

namespace App\Enums;

enum PromoCostBearer: string
{
    case Mummish = 'mummish';
    case Vendor = 'vendor';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Mummish => 'Mummish commission',
            self::Vendor => 'Vendor',
            self::Both => 'Both (split evenly)',
        };
    }

    public function helperText(): string
    {
        return match ($this) {
            self::Mummish => 'The discount is deducted from Mummish’s commission. Vendor payout stays based on the full item price.',
            self::Vendor => 'The discount is deducted from the vendor’s payout. Mummish commission stays based on the full item price.',
            self::Both => 'Half the discount comes from Mummish commission and half from the vendor payout.',
        };
    }
}
