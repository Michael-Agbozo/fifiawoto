<?php

use App\Models\Donation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rewrite donations.received_at into the format the model's date cast stores.
     *
     * Older edits went through the query builder and wrote raw "Y-m-d" strings
     * while the model writes "Y-m-d H:i:s". Only SQLite keeps the date column as
     * free text; MySQL / PostgreSQL DATE columns already normalise the value, so
     * nothing needs rewriting there.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        $format = (new Donation)->getDateFormat();

        DB::table('donations')
            ->select(['id', 'received_at'])
            ->whereNotNull('received_at')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($format) {
                foreach ($rows as $row) {
                    $normalized = Carbon::parse($row->received_at)->startOfDay()->format($format);

                    if ($normalized !== $row->received_at) {
                        DB::table('donations')->where('id', $row->id)->update(['received_at' => $normalized]);
                    }
                }
            });
    }

    /**
     * Data-only normalisation; nothing to reverse.
     */
    public function down(): void
    {
        //
    }
};
