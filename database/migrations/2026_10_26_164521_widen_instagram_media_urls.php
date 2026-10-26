<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Instagram CDN URLs routinely exceed 255 characters.
     */
    public function up(): void
    {
        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->text('media_url')->nullable()->change();
            $table->text('thumbnail_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->string('media_url')->nullable()->change();
            $table->string('thumbnail_url')->nullable()->change();
        });
    }
};
