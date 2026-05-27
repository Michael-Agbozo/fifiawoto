<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained('volunteer_applications')->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country');
            $table->string('role');
            $table->date('assigned_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('role');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteers');
    }
};
