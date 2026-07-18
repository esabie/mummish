<?php

namespace App\Enums;

enum ProductCondition: string
{
    case New = 'new';
    case FairlyUsed = 'fairly_used';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::FairlyUsed => 'Fairly used',
            self::Used => 'Used',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $condition) => [
                'value' => $condition->value,
                'label' => $condition->label(),
            ])
            ->values()
            ->all();
    }
}
