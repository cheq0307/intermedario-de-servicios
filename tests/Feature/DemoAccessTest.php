<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use App\Support\DemoMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_access_is_hidden_when_the_flag_is_disabled(): void
    {
        config(['demo.enabled' => false]);

        $this->get('/demo/acceso')->assertNotFound();
    }

    public function test_demo_access_lists_seeded_profiles_without_exposing_passwords(): void
    {
        config(['demo.enabled' => true]);
        Artisan::call('plaza:demo-marketplace');

        $this->get('/demo/acceso')
            ->assertOk()
            ->assertSee('Perfiles de prueba')
            ->assertSee('Comprador DEMO 1')
            ->assertSee('Proveedor DEMO 5')
            ->assertSee('Administrador DEMO 1')
            ->assertDontSee('password');
    }

    public function test_demo_access_logs_into_seeded_marketplace_and_admin_profiles(): void
    {
        config(['demo.enabled' => true]);
        Artisan::call('plaza:demo-marketplace');
        $user = User::where('email', 'comprador1.demo@example.test')->firstOrFail();
        $admin = AdminUser::where('email', 'administrador1.demo@example.test')->firstOrFail();

        $this->post('/demo/acceso', ['guard' => 'web', 'id' => $user->id])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::guard('web')->user()->is($user));

        $this->post('/demo/acceso', ['guard' => 'admin', 'id' => $admin->id])
            ->assertRedirect(route('admin.index'));
        $this->assertTrue(Auth::guard('admin')->user()->is($admin));
    }

    public function test_demo_access_rejects_normal_accounts(): void
    {
        config(['demo.enabled' => true]);
        $user = User::factory()->create(['email' => 'persona@example.com']);

        $this->post('/demo/acceso', ['guard' => 'web', 'id' => $user->id])
            ->assertForbidden();
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_demo_mode_forces_fake_payment_drivers(): void
    {
        config([
            'demo.enabled' => true,
            'marketplace.payment_driver' => 'stripe',
            'marketplace.vacancy_payment_driver' => 'mercadopago',
        ]);

        $this->app->make(DemoMode::class)->enforceFakePayments();

        $this->assertSame('fake', config('marketplace.payment_driver'));
        $this->assertSame('fake', config('marketplace.vacancy_payment_driver'));
    }
}
