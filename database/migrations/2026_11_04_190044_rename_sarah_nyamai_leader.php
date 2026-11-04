<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Correct the spelling of a board member's surname.
     */
    public function up(): void
    {
        DB::table('leaders')->where('name', 'Sarah Nyamai')->update(['name' => 'Sarah Nyamadi']);
    }

    /**
     * The old spelling was a mistake, so there is nothing to restore.
     */
    public function down(): void
    {
        //
    }
};
