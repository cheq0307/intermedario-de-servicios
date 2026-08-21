<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('status', 30)->default('pending');
            $table->string('disk', 40);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_id', 'type', 'status'], 'vendor_document_review_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_verification_documents');
    }
};