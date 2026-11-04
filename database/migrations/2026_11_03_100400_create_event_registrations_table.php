<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('registration_enabled')->default(false);
            $table->unsignedInteger('capacity')->nullable();
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 120);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->unsignedSmallInteger('party_size')->default(1);
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['registration_enabled', 'capacity']);
        });
    }
};
