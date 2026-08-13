<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('categories')->insertOrIgnore(collect([
            ['name' => 'Comida y bebidas', 'slug' => 'comida-bebidas'],
            ['name' => 'Transporte y taxi', 'slug' => 'transporte-taxi'],
            ['name' => 'Hogar y reparaciones', 'slug' => 'hogar-reparaciones'],
            ['name' => 'Construcción', 'slug' => 'construccion'],
            ['name' => 'Belleza y cuidado', 'slug' => 'belleza-cuidado'],
            ['name' => 'Tecnología', 'slug' => 'tecnologia'],
            ['name' => 'Tiendas y productos', 'slug' => 'tiendas-productos'],
            ['name' => 'Educación', 'slug' => 'educacion'],
            ['name' => 'Mascotas', 'slug' => 'mascotas'],
        ])->map(fn (array $category) => $category + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now])->all());

        Schema::create('user_category_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('interest_score')->default(0);
            $table->unsignedSmallInteger('behavior_score')->default(0);
            $table->timestamps();
            $table->primary(['user_id', 'category_id']);
            $table->index(['user_id', 'interest_score', 'behavior_score'], 'user_category_preference_score');
        });

        Schema::create('category_vendor', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['category_id', 'vendor_id']);
        });

        Schema::create('community_job_request', function (Blueprint $table) {
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_request_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['community_id', 'job_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_job_request');
        Schema::dropIfExists('category_vendor');
        Schema::dropIfExists('user_category_preferences');
    }
};
