<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postal_codes', function (Blueprint $table) {
            $table->id();
            $table->char('postal_code', 5);
            $table->string('settlement', 180);
            $table->string('settlement_type', 80)->nullable();
            $table->string('municipality', 180);
            $table->string('state', 120);
            $table->string('city', 180)->nullable();
            $table->string('state_code', 4)->nullable();
            $table->string('municipality_code', 8)->nullable();
            $table->string('settlement_code', 16)->nullable();
            $table->timestamps();
            $table->unique(['postal_code', 'settlement', 'municipality'], 'postal_codes_place_unique');
            $table->index(['postal_code', 'state']);
        });

        Schema::table('communities', function (Blueprint $table) {
            $table->char('postal_code', 5)->nullable()->after('state')->index();
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropIndex(['postal_code']);
            $table->dropColumn('postal_code');
        });
        Schema::dropIfExists('postal_codes');
    }
};
