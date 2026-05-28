<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('permalink');
            $table->text('caption')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index(['is_approved', 'is_hidden', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
