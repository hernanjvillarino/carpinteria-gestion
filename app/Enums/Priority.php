<?php

namespace App\Enums;

enum Priority: string
{
    case Baja = 'baja';
    case Normal = 'normal';
    case Alta = 'alta';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
