<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\PublicationDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicationDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_recovers_publication_after_verifying_email(): void
    {
        $user = User::factory()->unverified()->create(['account_type' => 'client']);
        $category = Category::create(['name' => 'Hogar', 'slug' => 'hogar', 'is_active' => true]);
        $payload = [
            'submission_token' => (string) Str::uuid(),
            'type' => 'job_request',
            'category_id' => $category->id,
            'body' => 'Necesito reparar una fuga de agua debajo del fregadero.',
            'title' => 'Reparación de fuga',
            'urgency' => 'soon',
            'budget_min' => '500',
            'budget_max' => '1200',
            'location_label' => 'Centro',
        ];

        $this->actingAs($user)
            ->post(route('posts.store'), $payload)
            ->assertRedirect(route('verification.notice'));

        $draft = PublicationDraft::whereBelongsTo($user)->firstOrFail();
        $this->assertSame($payload['body'], $draft->payload['body']);
        $this->assertSame($payload['title'], $draft->payload['title']);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)
            ->followingRedirects()
            ->get($verificationUrl)
            ->assertOk()
            ->assertSee('Recuperamos tu publicación')
            ->assertSee($payload['body'])
            ->assertSee($payload['title']);

        $this->actingAs($user->fresh())
            ->post(route('posts.store'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'job_request',
            'body' => $payload['body'],
        ]);
        $this->assertDatabaseMissing('publication_drafts', ['user_id' => $user->id]);
        $this->assertSame(1, Post::where('submission_token', $payload['submission_token'])->count());
    }

    public function test_validation_messages_never_expose_translation_keys(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);

        $response = $this->actingAs($user)
            ->from(route('dashboard'))
            ->post(route('posts.store'), [
                'submission_token' => (string) Str::uuid(),
                'type' => 'job_request',
                'body' => 'corto',
                'title' => 'x',
                'urgency' => 'normal',
            ]);

        $response->assertRedirect(route('dashboard'));
        $errors = session('errors')->all();
        $this->assertNotEmpty($errors);
        $this->assertStringNotContainsString('validation.', implode(' ', $errors));
    }
}
