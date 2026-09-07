<?php

namespace App\Enums;

enum SmsAudience: string
{
    case Newsletter = 'newsletter';
    case Vendors = 'vendors';
    case HealthProfessionals = 'health_professionals';
    case Customers = 'customers';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Newsletter => 'Newsletter subscribers',
            self::Vendors => 'Approved vendors',
            self::HealthProfessionals => 'Approved health professionals',
            self::Customers => 'Customer accounts',
            self::Custom => 'Custom phone numbers',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Newsletter => 'People who joined from the site footer.',
            self::Vendors => 'Vendors with an approved shop and a phone on file.',
            self::HealthProfessionals => 'Approved health professionals with a phone on file.',
            self::Customers => 'Customer accounts that have a phone number.',
            self::Custom => 'Paste one or more Ghana phone numbers.',
        };
    }
}
