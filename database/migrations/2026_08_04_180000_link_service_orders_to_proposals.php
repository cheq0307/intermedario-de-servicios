<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('job_request_id')->nullable()->after('vendor_id')->constrained()->nullOnDelete();
            $table->foreignId('job_proposal_id')->nullable()->after('job_request_id')->constrained()->nullOnDelete();
            $table->timestamp('started_at')->nullable()->after('accepted_at');
            $table->timestamp('due_at')->nullable()->after('started_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->unique('job_request_id');
            $table->unique('job_proposal_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['job_request_id']);
            $table->dropForeign(['job_proposal_id']);
            $table->dropUnique(['job_request_id']);
            $table->dropUnique(['job_proposal_id']);
            $table->dropColumn([
                'job_request_id',
                'job_proposal_id',
                'started_at',
                'due_at',
                'cancellation_reason',
            ]);
        });
    }
};
