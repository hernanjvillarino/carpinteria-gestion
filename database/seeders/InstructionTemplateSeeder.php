<?php

namespace Database\Seeders;

use App\Models\InstructionTemplate;
use Illuminate\Database\Seeder;

/** Indicaciones de ejemplo; el dueño las edita desde Equipo → Indicaciones precargadas. */
class InstructionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $texts = [
            'Verificar medidas en obra antes de cortar',
            'Respetar el sentido de la veta en frentes y laterales',
            'Tapacanto ABS 2 mm en todos los frentes',
            'Bisagras cierre suave en todas las puertas',
            'Correderas telescópicas con freno en cajones',
            'Sacar fotos del armado antes de desarmar para el traslado',
            'Coordinar el ingreso al barrio con la guardia el día anterior',
            'Proteger pisos y mesadas durante la instalación',
            'Dejar la obra limpia y retirar los recortes',
        ];

        foreach ($texts as $i => $text) {
            InstructionTemplate::firstOrCreate(['text' => $text], ['position' => $i + 1]);
        }
    }
}
