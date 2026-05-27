<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficiary_timeline_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['beneficiary_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_timeline_entries');
    }
};
