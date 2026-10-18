<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_requests', function (Blueprint $table): void {
            $table->foreignId('preferred_provider_id')
                ->nullable()
                ->after('client_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('scheduled_for')->nullable()->after('location_label');
            $table->index(['preferred_provider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('job_requests', function (Blueprint $table): void {
            $table->dropIndex(['preferred_provider_id', 'status']);
            $table->dropConstrainedForeignId('preferred_provider_id');
            $table->dropColumn('scheduled_for');
        });
    }
};
