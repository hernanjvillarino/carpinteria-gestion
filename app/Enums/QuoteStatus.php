<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Borrador = 'borrador';
    case Enviado = 'enviado';
    case Aceptado = 'aceptado';
    case Rechazado = 'rechazado';
    case Vencido = 'vencido';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviado => 'Enviado',
            self::Aceptado => 'Aceptado',
            self::Rechazado => 'Rechazado',
            self::Vencido => 'Vencido',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Borrador, self::Vencido => '!bg-stone-100 !text-stone-700',
            self::Enviado => '!bg-amber-100 !text-amber-800',
            self::Aceptado => '!bg-green-100 !text-green-800',
            self::Rechazado => '!bg-red-100 !text-red-800',
        };
    }
}
