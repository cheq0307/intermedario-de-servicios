<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('municipality', 120);
            $table->string('state', 120)->nullable();
            $table->decimal('distance_km', 8, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['name', 'municipality']);
            $table->index(['is_active', 'distance_km']);
        });

        $communityId = DB::table('communities')->insertGetId([
            'name' => 'Comunidad principal',
            'municipality' => 'Localidad inicial',
            'distance_km' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('community_id')->nullable()->after('city')->constrained()->nullOnDelete();
        });

        DB::table('users')->whereNull('community_id')->update(['community_id' => $communityId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('community_id');
        });
        Schema::dropIfExists('communities');
    }
};
