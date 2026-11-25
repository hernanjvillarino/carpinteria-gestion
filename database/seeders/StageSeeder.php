<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    /** Etapas iniciales; se pueden editar luego. */
    public const STAGES = [
        ['Consulta', true], ['Medición', true], ['Diseño', true], ['Presupuesto', true],
        ['Aprobado', true], ['Despiece', false], ['Corte', true], ['Armado', true],
        ['Terminación', true], ['Instalación', true], ['Entregado', true],
    ];

    public function run(): void
    {
        foreach (self::STAGES as $i => [$name, $visible]) {
            Stage::updateOrCreate(['position' => $i + 1], ['name' => $name, 'visible_to_client' => $visible]);
        }
    }
}
