<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_promotions', function (Blueprint $table) {
            $table->string('payment_provider', 30)->nullable()->after('currency');
            $table->string('provider_preference_id')->nullable()->unique()->after('payment_provider');
            $table->string('provider_payment_id')->nullable()->unique()->after('provider_preference_id');
            $table->text('checkout_url')->nullable()->after('provider_payment_id');
            $table->json('payment_payload')->nullable()->after('checkout_url');
        });
    }

    public function down(): void
    {
        Schema::table('post_promotions', function (Blueprint $table) {
            $table->dropUnique(['provider_preference_id']);
            $table->dropUnique(['provider_payment_id']);
            $table->dropColumn([
                'payment_provider',
                'provider_preference_id',
                'provider_payment_id',
                'checkout_url',
                'payment_payload',
            ]);
        });
    }
};
