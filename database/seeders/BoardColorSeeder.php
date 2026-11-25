<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Colores sugeridos por corteOPT; se pueden agregar más desde el sistema. */
class BoardColorSeeder extends Seeder
{
    public const COLORS = [
        'Blanco', 'Negro', 'Gris humo', 'Gris grafito', 'Aluminio', 'Almendra', 'Arena', 'Cajú',
        'Roble Dakar', 'Roble Mérida', 'Roble natural', 'Nogal', 'Nogal Terracota', 'Cedro',
        'Paraíso', 'Teka', 'Haya', 'Wengué', 'Fresno', 'Crudo',
    ];

    public function run(): void
    {
        DB::table('board_colors')->upsert(
            array_map(fn ($name) => ['name' => $name, 'created_at' => now(), 'updated_at' => now()], self::COLORS),
            ['name'],
        );
    }
}
