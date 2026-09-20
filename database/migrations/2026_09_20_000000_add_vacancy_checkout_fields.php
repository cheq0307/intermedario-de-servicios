<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_vacancies', function (Blueprint $table) {
            $table->string('payment_provider')->nullable();
            $table->string('provider_preference_id')->nullable();
            $table->text('checkout_url')->nullable();
            $table->json('payment_payload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_vacancies', fn (Blueprint $table) => $table->dropColumn(['payment_provider', 'provider_preference_id', 'checkout_url', 'payment_payload']));
    }
};
