<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnifiedLocalMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_targets_localities_and_notifies_matching_people_inside_and_outside_area(): void
    {
        $category = Category::query()->where('slug', 'transporte-taxi')->firstOrFail();
        $local = Community::query()->firstOrFail();
        $outside = Community::create([
            'name' => 'Comunidad vecina',
            'municipality' => 'Otro municipio',
            'postal_code' => '75000',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);
        $client = User::factory()->create(['community_id' => $local->id]);
        $insideUser = $this->commercialUser($local, $category, 'Taxi local');
        $outsideUser = $this->commercialUser($outside, $category, 'Taxi vecino');

        $this->actingAs($client, 'web')->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'job_request',
            'category_id' => $category->id,
            'community_ids' => [$local->id],
            'title' => 'Necesito un taxi al centro',
            'urgency' => 'urgent',
            'body' => 'Busco una persona disponible para realizar el traslado hoy.',
        ])->assertRedirect(route('dashboard'));

        $job = $client->jobRequests()->firstOrFail();
        $this->assertTrue($job->communities->contains($local));
        $this->assertSame('matching_request_local', $insideUser->notifications()->firstOrFail()->data['kind']);
        $this->assertSame('matching_request_nearby', $outsideUser->notifications()->firstOrFail()->data['kind']);
    }

    private function commercialUser(Community $community, Category $category, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'community_id' => $community->id]);
        $vendor = $user->vendor()->create([
            'display_name' => $name,
            'slug' => Str::slug($name).'-'.$user->id,
            'status' => 'active',
        ]);
        $vendor->categories()->attach($category);

        return $user;
    }
}
