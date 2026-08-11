<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostEngagementAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_like_unlike_comment_and_share_a_post(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'type' => 'job_request', 'body' => 'Necesito ayuda con una reparacion local.', 'published_at' => now()]);

        $this->actingAs($viewer)->post(route('posts.reactions.toggle', $post))->assertRedirect();
        $this->assertDatabaseHas('post_reactions', ['post_id' => $post->id, 'user_id' => $viewer->id]);
        $this->actingAs($viewer)->post(route('posts.reactions.toggle', $post))->assertRedirect();
        $this->assertDatabaseMissing('post_reactions', ['post_id' => $post->id, 'user_id' => $viewer->id]);

        $this->actingAs($viewer)->post(route('posts.comments.store', $post), ['body' => 'Puedo ayudarte manana.'])->assertRedirect();
        $this->assertDatabaseHas('post_comments', ['post_id' => $post->id, 'user_id' => $viewer->id]);

        $this->actingAs($viewer)->post(route('posts.shares.store', $post), ['channel' => 'clipboard'])->assertRedirect();
        $this->assertDatabaseHas('post_shares', ['post_id' => $post->id, 'channel' => 'clipboard']);
    }

    public function test_client_can_publish_images_and_the_feed_renders_them(): void
    {
        Storage::fake('public');
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'job_request',
            'title' => 'Reparar una puerta',
            'urgency' => 'normal',
            'body' => 'Necesito reparar una puerta de madera que no cierra.',
            'media' => [UploadedFile::fake()->createWithContent('puerta.jpg', base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2Q=='))],
        ])->assertRedirect(route('dashboard'));

        $media = PostMedia::firstOrFail();
        Storage::disk('public')->assertExists($media->path);
        $this->actingAs($client)->get(route('dashboard'))->assertOk()->assertSee($media->url);
    }

    public function test_unsupported_media_is_rejected(): void
    {
        Storage::fake('public');
        $client = User::factory()->create(['account_type' => 'client']);
        $this->actingAs($client)->from(route('dashboard'))->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'job_request',
            'title' => 'Trabajo de prueba',
            'urgency' => 'normal',
            'body' => 'Solicitud valida con un archivo que no esta permitido.',
            'media' => [UploadedFile::fake()->create('malware.exe', 5, 'application/octet-stream')],
        ])->assertRedirect(route('dashboard'))->assertSessionHasErrors('media.0');
    }
}
