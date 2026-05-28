<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('beneficiary_folders')->nullOnDelete();
            $table->string('disk')->default('beneficiaries');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scan_status')->default('pending');
            $table->timestamp('scan_checked_at')->nullable();
            $table->timestamps();

            $table->index(['beneficiary_id', 'folder_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_documents');
    }
};
