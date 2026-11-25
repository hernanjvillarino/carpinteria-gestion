<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Ingreso = 'ingreso';
    case Consumo = 'consumo';
    case Ajuste = 'ajuste';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
