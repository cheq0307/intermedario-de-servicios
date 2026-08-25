<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('post_id')->nullable()->after('job_request_id')->constrained()->nullOnDelete();
            $table->foreignId('initiated_by_user_id')->nullable()->after('post_id')->constrained('users')->nullOnDelete();
            $table->string('context_key')->nullable()->unique()->after('initiated_by_user_id');
            $table->foreignId('agreement_order_id')->nullable()->after('context_key')->constrained('orders')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('last_message_at');
            $table->timestamp('closed_at')->nullable()->after('expires_at');
            $table->string('closed_reason')->nullable()->after('closed_at');
            $table->timestamp('retention_until')->nullable()->after('closed_reason');
            $table->unsignedTinyInteger('extension_count')->default(0)->after('retention_until');
            $table->index(['type', 'state', 'expires_at'], 'conversations_negotiation_expiry_index');
            $table->index(['type', 'state', 'retention_until'], 'conversations_retention_index');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_negotiation_expiry_index');
            $table->dropIndex('conversations_retention_index');
            $table->dropUnique(['context_key']);
            $table->dropConstrainedForeignId('agreement_order_id');
            $table->dropConstrainedForeignId('initiated_by_user_id');
            $table->dropConstrainedForeignId('post_id');
            $table->dropColumn(['context_key', 'expires_at', 'closed_at', 'closed_reason', 'retention_until', 'extension_count']);
        });
    }
};
