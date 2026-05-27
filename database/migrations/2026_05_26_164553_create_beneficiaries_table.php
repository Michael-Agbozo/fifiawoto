<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('country');
            $table->string('region')->nullable();
            $table->string('category');
            $table->text('description');
            $table->string('status')->default('pending_review');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            // Loose reference to beneficiary_applications (no FK to avoid chicken-and-egg with conversion field below).
            $table->unsignedBigInteger('source_application_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category']);
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiaries');
    }
};
