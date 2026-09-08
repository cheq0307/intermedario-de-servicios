<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\Accounts\PruneUnverifiedAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneUnverifiedAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_removes_only_stale_unverified_accounts_without_history(): void
    {
        $removable = User::factory()->unverified()->create(['created_at' => now()->subDays(8)]);
        $recent = User::factory()->unverified()->create(['created_at' => now()->subDays(6)]);
        $verified = User::factory()->create(['created_at' => now()->subDays(20)]);
        $withHistory = User::factory()->unverified()->create(['created_at' => now()->subDays(20)]);
        Post::create([
            'user_id' => $withHistory->id,
            'type' => 'social',
            'body' => 'Esta actividad impide la eliminación automática.',
            'comments_enabled' => true,
            'published_at' => now()->subDays(19),
        ]);

        $affected = app(PruneUnverifiedAccounts::class)->prune();

        $this->assertSame(1, $affected);
        $this->assertDatabaseMissing('users', ['id' => $removable->id]);
        $this->assertDatabaseHas('users', ['id' => $recent->id]);
        $this->assertDatabaseHas('users', ['id' => $verified->id]);
        $this->assertDatabaseHas('users', ['id' => $withHistory->id]);
    }

    public function test_dry_run_reports_candidates_without_deleting_them(): void
    {
        $user = User::factory()->unverified()->create(['created_at' => now()->subDays(8)]);

        $this->artisan('plaza:prune-unverified-accounts --dry-run')
            ->expectsOutput('1 cuentas cumplen las condiciones; no se eliminó ninguna.')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
