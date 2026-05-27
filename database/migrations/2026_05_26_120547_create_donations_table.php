<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name');
            $table->string('donor_email')->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->default('manual');
            $table->string('external_reference')->nullable();
            $table->date('received_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
