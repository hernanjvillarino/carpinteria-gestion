<?php

namespace App\Enums;

/**
 * Ids de material que acepta la columna "Material" de corteOPT (formato v1).
 * Ver docs/corteopt-importacion.md.
 */
enum CorteOptMaterial: string
{
    case Melamina = 'melamina';
    case MelaminaMdf = 'melamina_mdf';
    case Mdf = 'mdf';
    case MdfLaqueado = 'mdf_laqueado';
    case Enchapado = 'enchapado';
    case Aglomerado = 'aglomerado';
    case Multilaminado = 'multilaminado';
    case Fenolico = 'fenolico';
    case Osb = 'osb';
    case Alistonado = 'alistonado';
    case Hardboard = 'hardboard';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Melamina => 'Melamina (sobre aglomerado)',
            self::MelaminaMdf => 'Melamina sobre MDF',
            self::Mdf => 'MDF / Fibrofácil crudo',
            self::MdfLaqueado => 'MDF laqueado / pintado',
            self::Enchapado => 'Enchapado natural',
            self::Aglomerado => 'Aglomerado crudo',
            self::Multilaminado => 'Multilaminado / terciado',
            self::Fenolico => 'Fenólico',
            self::Osb => 'OSB',
            self::Alistonado => 'Madera maciza / alistonado',
            self::Hardboard => 'Hardboard / chapadur',
            self::Otro => 'Otra placa',
        };
    }

    /** Medida de placa por defecto en corteOPT [largo, ancho] en mm. */
    public function defaultSheet(): array
    {
        return match ($this) {
            self::Melamina, self::MelaminaMdf => [2750, 1830],
            self::Mdf, self::MdfLaqueado, self::Enchapado, self::Aglomerado => [2600, 1830],
            self::Hardboard, self::Otro => [2440, 1830],
            default => [2440, 1220],
        };
    }

    public function hasGrainByDefault(): bool
    {
        return in_array($this, [self::Enchapado, self::Multilaminado, self::Fenolico, self::Alistonado], true);
    }
}
