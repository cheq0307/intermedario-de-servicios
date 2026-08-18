<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostComment;
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

        $this->actingAs($viewer)->post(route('posts.reactions.toggle', $post))
            ->assertRedirect()
            ->assertSessionHas('status', 'Marcaste esta publicación con Me gusta.');
        $this->assertDatabaseHas('post_reactions', ['post_id' => $post->id, 'user_id' => $viewer->id]);
        $this->actingAs($viewer)->get(route('dashboard'))->assertOk()->assertSee('Quitar Me gusta');
        $this->actingAs($viewer)->post(route('posts.reactions.toggle', $post))
            ->assertRedirect()
            ->assertSessionHas('status', 'Ya no te gusta esta publicación.');
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

    public function test_comments_are_paginated_and_only_the_author_can_edit_them(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $outsider = User::factory()->create();
        $post = Post::create(['user_id' => $author->id, 'type' => 'job_request', 'body' => 'Solicitud con conversación activa.', 'published_at' => now()]);

        foreach (range(1, 25) as $number) {
            PostComment::create(['post_id' => $post->id, 'user_id' => $viewer->id, 'body' => "Comentario número {$number}"]);
        }

        $this->actingAs($viewer)->get(route('posts.comments.index', $post))
            ->assertOk()
            ->assertSee('Comentario número 25')
            ->assertDontSee('Comentario número 5');

        $comment = PostComment::latest('id')->firstOrFail();
        $this->actingAs($viewer)->patch(route('posts.comments.update', $comment), ['body' => 'Comentario corregido por su autor.'])->assertRedirect();
        $this->assertDatabaseHas('post_comments', ['id' => $comment->id, 'body' => 'Comentario corregido por su autor.']);
        $this->actingAs($outsider)->patch(route('posts.comments.update', $comment), ['body' => 'Intento de edición no autorizado.'])->assertForbidden();
    }
}
