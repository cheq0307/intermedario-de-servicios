<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_open_every_operational_module(): void
    {
        $admin = AdminUser::factory()->create();

        foreach ([
            'admin.jobs.index' => 'Empleo y postulaciones',
            'admin.operations.index' => 'Operaciones',
            'admin.promotions.index' => 'Publicidad',
            'admin.payments.index' => 'Pagos y conciliación',
        ] as $route => $heading) {
            $this->actingAs($admin, 'admin')
                ->get(route($route))
                ->assertOk()
                ->assertSee($heading)
                ->assertSee('Cuentas')
                ->assertDontSee('>Proveedores</a>', false);
        }
    }

    public function test_regular_account_cannot_open_operational_modules(): void
    {
        $account = User::factory()->create();

        foreach ([
            'admin.jobs.index',
            'admin.operations.index',
            'admin.promotions.index',
            'admin.payments.index',
        ] as $route) {
            $this->actingAs($account, 'web')->get(route($route))->assertRedirect(route('admin.login'));
        }
    }
}
