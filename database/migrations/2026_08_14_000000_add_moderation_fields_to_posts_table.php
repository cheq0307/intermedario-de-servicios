<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('removed_at')->nullable()->index()->after('published_at');
            $table->foreignId('removed_by_user_id')->nullable()->after('removed_at')->constrained('users')->nullOnDelete();
            $table->text('removal_reason')->nullable()->after('removed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['removed_by_user_id']);
            $table->dropColumn(['removed_at', 'removed_by_user_id', 'removal_reason']);
        });
    }
};
