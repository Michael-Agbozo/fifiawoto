<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiary_documents', function (Blueprint $table) {
            $table->timestamp('encrypted_at')->nullable();
        });

        Schema::table('beneficiary_application_documents', function (Blueprint $table) {
            $table->string('scan_status', 20)->nullable();
            $table->timestamp('scan_checked_at')->nullable();
            $table->timestamp('encrypted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('beneficiary_documents', function (Blueprint $table) {
            $table->dropColumn('encrypted_at');
        });

        Schema::table('beneficiary_application_documents', function (Blueprint $table) {
            $table->dropColumn(['scan_status', 'scan_checked_at', 'encrypted_at']);
        });
    }
};
