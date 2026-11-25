<?php

namespace App\Enums;

enum MaterialUnit: string
{
    case Placa = 'placa';
    case Metro = 'm';
    case MetroCuadrado = 'm2';
    case Unidad = 'unidad';
    case Kilo = 'kg';
    case Litro = 'litro';

    public function label(): string
    {
        return match ($this) {
            self::Placa => 'placa',
            self::Metro => 'metro',
            self::MetroCuadrado => 'm²',
            self::Unidad => 'unidad',
            self::Kilo => 'kg',
            self::Litro => 'litro',
        };
    }
}
