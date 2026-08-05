<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
            $table->string('order_status_before')->nullable()->after('description');
            $table->string('resolution_outcome')->nullable()->after('resolution');
            $table->unique('order_id');
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['dispute_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispute_messages');
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropUnique(['order_id']);
            $table->dropUnique(['public_id']);
            $table->dropColumn(['public_id', 'order_status_before', 'resolution_outcome']);
        });
    }
};
