<?php

namespace App\Enums;

/** Estado de un documento exigido para entrar a un barrio. */
enum DocumentStatus: string
{
    case Falta = 'falta';
    case Vencido = 'vencido';
    case PorVencer = 'por_vencer';
    case Vigente = 'vigente';

    public function label(): string
    {
        return match ($this) {
            self::Falta => 'Falta',
            self::Vencido => 'Vencido',
            self::PorVencer => 'Por vencer',
            self::Vigente => 'Vigente',
        };
    }

    /** Con este estado el empleado puede entrar (por vencer todavía sirve). */
    public function allowsEntry(): bool
    {
        return $this === self::Vigente || $this === self::PorVencer;
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Falta => 'bg-stone-200 text-stone-700',
            self::Vencido => 'bg-red-100 text-red-800',
            self::PorVencer => 'bg-amber-100 text-amber-800',
            self::Vigente => 'bg-green-100 text-green-800',
        };
    }
}
