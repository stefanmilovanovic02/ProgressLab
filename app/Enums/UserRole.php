<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Trainer = 'trainer';
    case Paid = 'paid';
    case Admin = 'admin';
    case Owner = 'owner';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Trainer => 'Trainer',
            self::Paid => 'Paid',
            self::Admin => 'Admin',
            self::Owner => 'Owner',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Admin, self::Owner], true);
    }
}
