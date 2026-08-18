<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('employer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('community_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 140);
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->unsignedBigInteger('salary_min_amount')->nullable();
            $table->unsignedBigInteger('salary_max_amount')->nullable();
            $table->string('pay_period', 20)->default('monthly');
            $table->string('work_mode', 20)->default('onsite');
            $table->string('contract_type', 30)->default('unspecified');
            $table->string('schedule')->nullable();
            $table->unsignedSmallInteger('vacancies_count')->default(1);
            $table->unsignedBigInteger('publication_fee_amount')->default(0);
            $table->char('currency', 3)->default('MXN');
            $table->string('status', 30)->default('pending_payment');
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'community_id', 'published_at']);
        });
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $table->text('cover_letter');
            $table->string('status', 30)->default('submitted');
            $table->timestamps();
            $table->unique(['job_vacancy_id', 'applicant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_vacancies');
    }
};