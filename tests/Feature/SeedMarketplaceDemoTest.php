<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeedMarketplaceDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_visible_idempotent_marketplace_scenario(): void
    {
        Storage::fake('public');

        $this->assertSame(0, Artisan::call('plaza:demo-marketplace'));
        $this->assertSame(0, Artisan::call('plaza:demo-marketplace'));

        $this->assertDatabaseCount('users', 20);
        $this->assertDatabaseCount('admin_users', 5);
        $this->assertDatabaseCount('vendors', 5);
        $this->assertDatabaseCount('listings', 1);
        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('job_vacancies', 1);
        $this->assertDatabaseCount('job_applications', 1);
        $this->assertSame(20, User::where('email', 'like', '%.demo@example.test')->count());
        $this->assertSame(5, AdminUser::where('email', 'like', '%.demo@example.test')->count());
        foreach (['comprador', 'proveedor', 'empleador', 'postulante'] as $profile) {
            $this->assertSame(5, User::where('email', 'like', $profile.'%.demo@example.test')->count());
        }
        $this->assertTrue(User::where('email', 'proveedor1.demo@example.test')->firstOrFail()->hasRole('provider'));
        $this->assertSame('awaiting_payment', Order::firstOrFail()->status->value);
        $this->assertSame('published', JobVacancy::firstOrFail()->status);
        $this->assertSame('submitted', JobApplication::firstOrFail()->status);
        $this->assertNotNull(Post::firstOrFail()->published_at);
        Storage::disk('public')->assertExists('demo/producto-plaza-local.svg');
    }
}
