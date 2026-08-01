<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_type')->default('client')->after('email');
            $table->string('phone', 30)->nullable()->after('account_type');
            $table->string('avatar_path')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('city')->nullable()->after('bio');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('last_seen_at')->nullable()->after('longitude');
            $table->index(['account_type', 'city']);
        });

        Schema::create('job_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('budget_min_amount')->nullable();
            $table->unsignedBigInteger('budget_max_amount')->nullable();
            $table->char('currency', 3)->default('MXN');
            $table->string('urgency')->default('normal');
            $table->string('status')->default('draft');
            $table->string('location_label')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'category_id', 'published_at']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('job_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('body');
            $table->boolean('comments_enabled')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['type', 'published_at']);
            $table->index(['user_id', 'published_at']);
        });

        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();
            $table->index(['post_id', 'position']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('job_request_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index('last_message_at');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('muted_until')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'last_read_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('favoriteable_type');
            $table->unsignedBigInteger('favoriteable_id');
            $table->timestamps();
            $table->unique(['user_id', 'favoriteable_type', 'favoriteable_id'], 'favorites_user_target_unique');
            $table->index(['favoriteable_type', 'favoriteable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('post_media');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('job_requests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type', 'city']);
            $table->dropColumn([
                'account_type',
                'phone',
                'avatar_path',
                'bio',
                'city',
                'latitude',
                'longitude',
                'last_seen_at',
            ]);
        });
    }
};
