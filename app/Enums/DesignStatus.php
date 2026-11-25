<?php

namespace App\Enums;

enum DesignStatus: string
{
    case Borrador = 'borrador';
    case Enviado = 'enviado';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado'; // el cliente pidió cambios

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviado => 'Esperando aprobación',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Cambios pedidos',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Borrador => '!bg-stone-100 !text-stone-700',
            self::Enviado => '!bg-amber-100 !text-amber-800',
            self::Aprobado => '!bg-green-100 !text-green-800',
            self::Rechazado => '!bg-red-100 !text-red-800',
        };
    }
}
