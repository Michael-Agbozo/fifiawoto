<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indexes for admin listing filters not already covered by the composite
     * (event_id, received_at), (status, created_at) and (status, starts_at) indexes.
     */
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->index('received_at');
            $table->index('payment_method');
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex(['received_at']);
            $table->dropIndex(['payment_method']);
        });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
