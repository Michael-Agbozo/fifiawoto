<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('location');
            $table->string('country');
            $table->longText('description');
            $table->longText('activities')->nullable();
            $table->longText('expected_impact')->nullable();
            $table->longText('volunteer_opportunities')->nullable();
            $table->unsignedBigInteger('goal_cents')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
