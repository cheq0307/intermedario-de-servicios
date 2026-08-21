<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('avatar_disk', 40)->default('public')->after('avatar_path'));
        Schema::table('post_media', fn (Blueprint $table) => $table->string('disk', 40)->default('public')->after('path'));
    }

    public function down(): void
    {
        Schema::table('post_media', fn (Blueprint $table) => $table->dropColumn('disk'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('avatar_disk'));
    }
};