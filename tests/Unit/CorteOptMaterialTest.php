<?php

namespace Tests\Unit;

use App\Enums\CorteOptMaterial;
use PHPUnit\Framework\TestCase;

class CorteOptMaterialTest extends TestCase
{
    public function test_defaults_match_corteopt_format_v1(): void
    {
        $this->assertSame([2750, 1830], CorteOptMaterial::Melamina->defaultSheet());
        $this->assertSame([2600, 1830], CorteOptMaterial::Mdf->defaultSheet());
        $this->assertSame([2440, 1220], CorteOptMaterial::Multilaminado->defaultSheet());
        $this->assertSame([2440, 1830], CorteOptMaterial::Hardboard->defaultSheet());
        $this->assertTrue(CorteOptMaterial::Enchapado->hasGrainByDefault());
        $this->assertFalse(CorteOptMaterial::Melamina->hasGrainByDefault());
        $this->assertCount(12, CorteOptMaterial::cases());
    }
}
