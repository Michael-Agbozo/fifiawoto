<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->uuid('public_sponsorship_id')->nullable()->unique();
            $table->boolean('sponsorship_published')->default(false)->index();
            $table->string('public_display_name', 60)->nullable();
            $table->text('public_story')->nullable();
            $table->string('public_photo_path')->nullable();
            $table->string('public_country', 80)->nullable();
            $table->timestamp('guardian_consent_at')->nullable();
            $table->string('guardian_consent_reference')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropColumn(['public_sponsorship_id', 'sponsorship_published', 'public_display_name', 'public_story', 'public_photo_path', 'public_country', 'guardian_consent_at', 'guardian_consent_reference']);
        });
    }
};
