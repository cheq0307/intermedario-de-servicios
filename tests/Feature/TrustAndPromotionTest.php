<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Post;
use App\Models\PostPromotion;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrustAndPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_and_trust_verification_are_separate_decisions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $superadmin = User::factory()->create();
        $superadmin->syncRoles([Role::findOrCreate('superadmin')]);
        $provider = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Proveedor aprobado',
            'slug' => 'proveedor-aprobado',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->patch(route('admin.vendors.verify', $vendor), [
            'verification_level' => 'identity',
            'verification_note' => 'Identificación oficial contrastada.',
        ])->assertForbidden();

        $this->actingAs($superadmin)->patch(route('admin.vendors.verify', $vendor), [
            'verification_level' => 'identity',
            'verification_note' => 'Identificación oficial contrastada.',
        ])->assertRedirect();

        $vendor->refresh();
        $this->assertNotNull($vendor->verified_at);
        $this->assertSame($superadmin->id, $vendor->verified_by_user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vendor.verified', 'subject_id' => $vendor->id]);
    }

    public function test_user_can_request_a_sponsored_campaign_only_for_own_offer(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::query()->firstOrFail();
        $vendor = Vendor::create(['user_id' => $user->id, 'display_name' => 'Local', 'slug' => 'local', 'status' => 'active']);
        $otherVendor = Vendor::create(['user_id' => $other->id, 'display_name' => 'Otro', 'slug' => 'otro', 'status' => 'active']);
        $listing = Listing::create(['vendor_id' => $vendor->id, 'category_id' => $category->id, 'type' => 'service', 'name' => 'Servicio local', 'slug' => 'servicio-local', 'price_type' => 'quote', 'is_active' => true]);
        $otherListing = Listing::create(['vendor_id' => $otherVendor->id, 'category_id' => $category->id, 'type' => 'service', 'name' => 'Servicio ajeno', 'slug' => 'servicio-ajeno', 'price_type' => 'quote', 'is_active' => true]);
        $post = Post::create(['user_id' => $user->id, 'vendor_id' => $vendor->id, 'listing_id' => $listing->id, 'type' => 'service', 'body' => 'Servicio local', 'published_at' => now()]);
        $otherPost = Post::create(['user_id' => $other->id, 'vendor_id' => $otherVendor->id, 'listing_id' => $otherListing->id, 'type' => 'service', 'body' => 'Servicio ajeno', 'published_at' => now()]);

        $this->actingAs($user)->post(route('promotions.store'), ['post_id' => $post->id, 'duration_days' => 7])->assertRedirect();
        $this->assertDatabaseHas('post_promotions', ['post_id' => $post->id, 'status' => 'pending_payment', 'amount' => 4900]);

        $this->post(route('promotions.store'), ['post_id' => $otherPost->id, 'duration_days' => 7])->assertSessionHasErrors('post_id');
        $this->assertSame(1, PostPromotion::count());
    }

    public function test_only_active_paid_campaign_is_exposed_as_sponsored(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'type' => 'service', 'body' => 'Oferta', 'published_at' => now()]);
        PostPromotion::create(['post_id' => $post->id, 'user_id' => $user->id, 'status' => 'active', 'duration_days' => 7, 'amount' => 4900, 'paid_at' => now(), 'starts_at' => now()->subMinute(), 'ends_at' => now()->addDays(7)]);

        $this->assertNotNull($post->fresh()->activePromotion);
    }
}
