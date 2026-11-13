<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            UserRole::Admin => 'Admin',
            UserRole::Kasir => 'Kasir',
        };
    }

    public function isAdmin(): bool
    {
        return $this === UserRole::Admin;
    }

    public function isKasir(): bool
    {
        return $this === UserRole::Kasir;
    }
}
