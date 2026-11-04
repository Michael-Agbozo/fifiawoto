<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->string('source', 120)->default('direct');
            $table->string('medium', 120)->nullable();
            $table->string('campaign', 120)->nullable();
            $table->char('visitor_hash', 64);
            $table->timestamp('created_at')->index();
        });

        Schema::create('site_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('source', 120)->default('direct');
            $table->string('medium', 120)->nullable();
            $table->string('campaign', 120)->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_conversions');
        Schema::dropIfExists('page_visits');
    }
};
