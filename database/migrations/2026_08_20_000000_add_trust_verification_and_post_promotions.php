<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('verified_by_user_id')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->string('verification_level', 30)->nullable()->after('verified_by_user_id');
            $table->text('verification_note')->nullable()->after('verification_level');
        });

        Schema::create('post_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending_payment')->index();
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedInteger('amount')->default(0);
            $table->char('currency', 3)->default('MXN');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamps();

            $table->index(['post_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_promotions');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['verified_by_user_id']);
            $table->dropColumn(['verified_by_user_id', 'verification_level', 'verification_note']);
        });
    }
};
