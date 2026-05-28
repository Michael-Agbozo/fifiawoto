<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_role');
            $table->string('photo_path')->nullable();
            $table->text('quote');
            $table->string('video_url')->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['featured', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
