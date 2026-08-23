<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type')->default('direct')->after('direct_key');
            $table->string('state')->default('active')->after('type');
            $table->timestamp('archived_at')->nullable()->after('last_message_at');
            $table->unique('order_id');
            $table->index(['type', 'state', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['type', 'state', 'last_message_at']);
            $table->dropUnique(['order_id']);
            $table->dropColumn(['type', 'state', 'archived_at']);
        });
    }
};
