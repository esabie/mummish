<?php

namespace App\Enums;

enum UserRole: string
{
    case Vendor = 'vendor';
    case Customer = 'customer';
    case Admin = 'admin';
}
