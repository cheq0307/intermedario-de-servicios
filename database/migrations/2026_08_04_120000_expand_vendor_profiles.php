<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('specialty')->nullable()->after('description');
            $table->string('service_area')->nullable()->after('specialty');
            $table->unsignedSmallInteger('years_experience')->nullable()->after('service_area');
            $table->string('availability_status')->default('available')->after('years_experience');
            $table->text('certifications')->nullable()->after('availability_status');
            $table->text('tools')->nullable()->after('certifications');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'specialty',
                'service_area',
                'years_experience',
                'availability_status',
                'certifications',
                'tools',
            ]);
        });
    }
};
