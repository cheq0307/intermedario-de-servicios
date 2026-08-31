<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\JobProposal;
use App\Models\JobRequest;
use App\Models\Listing;
use App\Models\Post;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_can_edit_own_product(): void
    {
        [$provider, $post, $listing] = $this->productPost();

        $this->actingAs($provider)->get(route('posts.edit', $post))->assertOk()->assertSee('Producto original');
        $this->actingAs($provider)->put(route('posts.update', $post), ['category_id' => $post->category_id, 'title' => 'Producto actualizado', 'price_type' => 'fixed', 'price' => '125.50', 'stock' => 8, 'body' => 'Descripción actualizada del producto local.'])->assertRedirect();

        $this->assertDatabaseHas('listings', ['id' => $listing->id, 'name' => 'Producto actualizado', 'price_amount' => 12550, 'stock' => 8]);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'body' => 'Descripción actualizada del producto local.']);
    }

    public function test_client_can_edit_request_only_before_receiving_proposals(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $category = Category::create(['name' => 'Hogar', 'slug' => 'hogar', 'is_active' => true]);
        $job = JobRequest::create(['public_id' => (string) Str::uuid(), 'client_id' => $client->id, 'category_id' => $category->id, 'title' => 'Solicitud original', 'description' => 'Descripción original de la solicitud.', 'status' => 'published', 'urgency' => 'normal', 'published_at' => now()]);
        $post = Post::create(['user_id' => $client->id, 'job_request_id' => $job->id, 'category_id' => $category->id, 'type' => 'job_request', 'body' => $job->description, 'published_at' => now()]);

        $this->actingAs($client)->put(route('posts.update', $post), ['category_id' => $category->id, 'title' => 'Solicitud actualizada', 'budget_min' => 200, 'budget_max' => 500, 'urgency' => 'soon', 'location_label' => 'Centro', 'body' => 'Descripción actualizada antes de recibir propuestas.'])->assertRedirect();
        $this->assertSame('Solicitud actualizada', $job->fresh()->title);

        $provider = User::factory()->create(['account_type' => 'provider']);
        JobProposal::create(['public_id' => (string) Str::uuid(), 'job_request_id' => $job->id, 'provider_id' => $provider->id, 'amount' => 30000, 'message' => 'Propuesta completa para realizar este trabajo.', 'estimated_days' => 1, 'status' => 'pending']);
        $this->actingAs($client)->get(route('posts.edit', $post))->assertStatus(422);
    }

    public function test_user_cannot_edit_another_users_post(): void
    {
        [, $post] = $this->productPost();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($outsider)->put(route('posts.update', $post), ['body' => 'Intento de modificación no autorizado.'])->assertForbidden();
    }

    private function productPost(): array
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $category = Category::create(['name' => 'Productos', 'slug' => 'productos', 'is_active' => true]);
        $vendor = Vendor::create(['user_id' => $provider->id, 'display_name' => 'Tienda', 'slug' => 'tienda-'.Str::lower(Str::random(5)), 'status' => 'active']);
        $listing = Listing::create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'type' => 'product', 'name' => 'Producto original', 'slug' => 'producto-original', 'description' => 'Descripción original del producto local.', 'price_type' => 'fixed', 'price_amount' => 10000, 'stock' => 3, 'is_active' => true]);
        $post = Post::create(['user_id' => $provider->id, 'vendor_id' => $vendor->id, 'listing_id' => $listing->id, 'category_id' => $category->id, 'type' => 'product', 'body' => $listing->description, 'published_at' => now()]);

        return [$provider, $post, $listing];
    }
}
