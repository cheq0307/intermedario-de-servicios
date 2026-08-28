<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('account_status', 20)->default('active')->index()->after('account_type');
            $table->text('account_status_reason')->nullable()->after('account_status');
            $table->timestamp('account_status_changed_at')->nullable()->index()->after('account_status_reason');
            $table->foreignId('account_status_changed_by_user_id')->nullable()->after('account_status_changed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('account_status_changed_by_user_id');
            $table->dropIndex(['account_status']);
            $table->dropIndex(['account_status_changed_at']);
            $table->dropColumn(['account_status', 'account_status_reason', 'account_status_changed_at']);
        });
    }
};
