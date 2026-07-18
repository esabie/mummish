<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Active = 'active';
    case Draft = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Draft => 'Draft',
        };
    }
}
