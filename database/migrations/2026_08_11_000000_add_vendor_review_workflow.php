<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
            $table->index(['status', 'submitted_at']);
        });

        DB::table('vendors')->where('status', 'pending')->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['status', 'submitted_at']);
            $table->dropColumn(['submitted_at', 'reviewed_at', 'rejection_reason']);
        });
    }
};
