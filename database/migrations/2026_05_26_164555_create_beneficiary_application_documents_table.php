<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_application_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('beneficiary_application_id');
            $table->foreign('beneficiary_application_id', 'bad_app_doc_foreign')
                  ->references('id')
                  ->on('beneficiary_applications')
                  ->onDelete('cascade');
            $table->string('disk')->default('beneficiaries');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_application_documents');
    }
};
