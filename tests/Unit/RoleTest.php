<?php

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_each_role_has_a_home_route_and_label(): void
    {
        $this->assertSame('admin.dashboard', Role::Admin->homeRoute());
        $this->assertSame('taller.index', Role::Carpintero->homeRoute());
        $this->assertSame('portal.index', Role::Cliente->homeRoute());
        $this->assertSame('Carpintero', Role::Carpintero->label());
    }
}
