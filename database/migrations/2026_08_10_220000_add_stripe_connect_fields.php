<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->unique();
            $table->boolean('stripe_details_submitted')->default(false);
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
        });
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropUnique(['stripe_account_id']);
            $table->dropColumn(['stripe_account_id', 'stripe_details_submitted', 'stripe_charges_enabled', 'stripe_payouts_enabled']);
        });
    }
};
