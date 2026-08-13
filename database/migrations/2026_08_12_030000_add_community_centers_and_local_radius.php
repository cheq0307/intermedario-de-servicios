<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('state');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('default_radius_km', 6, 2)->default(8)->after('longitude');
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name']);
            $table->dropColumn(['latitude', 'longitude', 'default_radius_km']);
        });
    }
};
