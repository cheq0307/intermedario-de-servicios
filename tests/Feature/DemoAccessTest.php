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

    public function test_demo_access_is_available_in_staging_when_explicitly_enabled(): void
    {
        $this->app->detectEnvironment(fn (): string => 'staging');
        config(['demo.enabled' => true]);

        Artisan::call('plaza:demo-marketplace');

        $this->get('/demo/acceso')
            ->assertOk()
            ->assertSee('Perfiles de prueba');
    }

    public function test_demo_access_remains_hidden_in_production_even_when_flag_is_enabled(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['demo.enabled' => true]);

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
            ->assertSee(route('demo.admin-access'))
            ->assertDontSee('password');

        $this->get(route('demo.admin-access'))
            ->assertOk()
            ->assertSee('Administrador DEMO 1')
            ->assertSee(route('demo.admin-access.store'));
    }

    public function test_demo_access_groups_legacy_unnumbered_profiles_without_failing(): void
    {
        config(['demo.enabled' => true]);
        Artisan::call('plaza:demo-marketplace');
        User::factory()->create([
            'name' => 'Comprador DEMO heredado',
            'email' => 'comprador.demo@example.test',
        ]);

        $this->get('/demo/acceso')
            ->assertOk()
            ->assertSee('Compradores y solicitantes')
            ->assertSee('Comprador DEMO heredado');
    }

    public function test_demo_access_identifies_the_configured_v2_preproduction_release(): void
    {
        config([
            'demo.enabled' => true,
            'app.release_version' => '2.0.0-rc.4',
            'app.release_channel' => 'preproduction',
        ]);
        Artisan::call('plaza:demo-marketplace');

        $this->get('/demo/acceso')
            ->assertOk()
            ->assertSee('V2 · Preproducción');
    }

    public function test_demo_access_omits_release_badge_when_release_metadata_is_empty(): void
    {
        config([
            'demo.enabled' => true,
            'app.release_version' => '',
            'app.release_channel' => '',
        ]);
        Artisan::call('plaza:demo-marketplace');

        $this->get('/demo/acceso')
            ->assertOk()
            ->assertDontSee('Preproducción');
    }

    public function test_demo_access_logs_into_seeded_marketplace_and_admin_profiles(): void
    {
        config(['demo.enabled' => true]);
        Artisan::call('plaza:demo-marketplace');
        $user = User::where('email', 'comprador1.demo@example.test')->firstOrFail();
        $admin = AdminUser::where('email', 'administrador1.demo@example.test')->firstOrFail();

        $this->post('/demo/acceso', ['id' => $user->id])
            ->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::guard('web')->user()->is($user));

        Auth::guard('web')->logout();
        $this->post(route('demo.admin-access.store'), ['id' => $admin->id])
            ->assertRedirect(route('admin.index'));
        $this->assertTrue(Auth::guard('admin')->user()->is($admin));
        $this->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Centro de operación');
    }

    public function test_demo_access_rejects_normal_accounts(): void
    {
        config(['demo.enabled' => true]);
        $user = User::factory()->create(['email' => 'persona@example.com']);

        $this->post('/demo/acceso', ['id' => $user->id])
            ->assertForbidden();
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_demo_mode_keeps_all_payment_flows_disabled(): void
    {
        config([
            'demo.enabled' => true,
            'marketplace.payment_driver' => 'stripe',
            'marketplace.vacancy_payment_driver' => 'mercadopago',
        ]);

        $this->app->make(DemoMode::class)->enforcePaymentFreeRelease();

        $this->assertFalse(config('marketplace.payments_enabled'));
        $this->assertFalse(config('marketplace.allow_fake_payments'));
    }
}
