<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('volunteers_notified_at')->nullable()->after('published_at');
        });

        // Events created before this column existed already had invitations
        // sent on create, so mark them as notified to avoid a second blast.
        DB::table('events')->update(['volunteers_notified_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('volunteers_notified_at');
        });
    }
};
