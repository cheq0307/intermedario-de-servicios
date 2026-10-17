<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Conversation;
use App\Models\JobApplication;
use App\Models\JobProposal;
use App\Models\JobRequest;
use App\Models\JobVacancy;
use App\Models\Message;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostMedia;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\Review;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
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
        $this->assertDatabaseCount('listings', 10);
        $this->assertDatabaseCount('posts', 15);
        $this->assertDatabaseCount('orders', 5);
        $this->assertDatabaseCount('job_vacancies', 5);
        $this->assertDatabaseCount('job_applications', 10);
        $this->assertDatabaseCount('job_requests', 5);
        $this->assertDatabaseCount('job_proposals', 10);
        $this->assertDatabaseCount('conversations', 10);
        $this->assertDatabaseCount('messages', 30);
        $this->assertDatabaseCount('post_media', 16);
        $this->assertDatabaseCount('post_comments', 30);
        $this->assertDatabaseCount('post_reactions', 45);
        $this->assertDatabaseCount('post_shares', 15);
        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseCount('support_tickets', 2);
        $this->assertDatabaseCount('support_messages', 4);
        $this->assertSame(20, User::where('email', 'like', '%.demo@example.test')->count());
        $this->assertSame(5, AdminUser::where('email', 'like', '%.demo@example.test')->count());
        foreach (['comprador', 'proveedor', 'empleador', 'postulante'] as $profile) {
            $this->assertSame(5, User::where('email', 'like', $profile.'%.demo@example.test')->count());
        }
        $this->assertTrue(User::where('email', 'proveedor1.demo@example.test')->firstOrFail()->hasRole('provider'));
        $this->assertSame(5, JobRequest::whereIn('status', ['published', 'in_conversation', 'assigned', 'completed'])->count());
        $this->assertSame(10, JobProposal::count());
        $this->assertSame(10, Conversation::where('state', 'active')->count());
        $this->assertSame(30, Message::whereNotNull('body')->count());
        $this->assertTrue(Order::where('status', 'completed')->exists());
        $this->assertTrue(Order::where('status', 'in_progress')->exists());
        $this->assertSame(5, JobVacancy::where('status', 'published')->count());
        $this->assertSame(10, JobApplication::count());
        $this->assertSame(2, Review::where('is_visible', true)->count());
        $this->assertSame(2, SupportTicket::count());
        $this->assertSame(4, SupportMessage::count());
        $this->assertSame(30, PostComment::count());
        $this->assertSame(45, PostReaction::count());
        $this->assertSame(15, PostShare::count());
        $this->assertNotNull(Post::firstOrFail()->published_at);
        $this->assertTrue(PostMedia::where('type', 'video')->exists());
        Storage::disk('public')->assertExists('demo/publicacion-01.webp');
        $this->assertSame(
            15,
            PostMedia::where('type', 'image')->where('path', 'like', 'demo/%.webp')->count(),
        );
        Storage::disk('public')->assertExists('demo/plaza-local-demo.mp4');
    }
}
