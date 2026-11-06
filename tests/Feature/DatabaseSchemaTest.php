<?php

use Illuminate\Support\Facades\Schema;

it('keeps index names within the 64 characters MySQL allows', function () {
    $tooLong = collect(Schema::getTableListing(schemaQualified: false))
        ->flatMap(fn (string $table) => Schema::getIndexes($table))
        ->pluck('name')
        ->filter(fn (string $name) => strlen($name) > 64)
        ->values()
        ->all();

    expect($tooLong)->toBe([]);
});
