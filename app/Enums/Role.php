<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Carpintero = 'carpintero';
    case Cliente = 'cliente';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Carpintero => 'Carpintero',
            self::Cliente => 'Cliente',
        };
    }

    /** Ruta de inicio de cada rol después del login. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Carpintero => 'taller.index',
            self::Cliente => 'portal.index',
        };
    }
}
