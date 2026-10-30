<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->string('public_sponsorship_slug', 100)->nullable()->unique();
        });

        DB::table('beneficiaries')->whereNotNull('public_display_name')->orderBy('id')->chunkById(100, function ($beneficiaries): void {
            foreach ($beneficiaries as $beneficiary) {
                if (blank($beneficiary->public_display_name)) {
                    continue;
                }

                $base = Str::limit(Str::slug(Str::before($beneficiary->public_display_name, ' (Demo)')), 80, '') ?: 'child';
                $slug = $base;

                while (DB::table('beneficiaries')->where('public_sponsorship_slug', $slug)->exists()) {
                    $slug = $base.'-'.Str::lower(Str::random(8));
                }

                DB::table('beneficiaries')->where('id', $beneficiary->id)->update(['public_sponsorship_slug' => $slug]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropUnique(['public_sponsorship_slug']);
            $table->dropColumn('public_sponsorship_slug');
        });
    }
};
