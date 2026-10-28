<?php

use App\Support\CsvCell;

it('prefixes cells that a spreadsheet would treat as formulas', function (string $input) {
    expect(CsvCell::escape($input))->toBe("'".$input);
})->with([
    '=HYPERLINK("http://evil.test","x")',
    '+1+1',
    '-2+3',
    '@SUM(A1)',
    "\tTAB",
    "\rCR",
]);

it('leaves safe values untouched', function () {
    expect(CsvCell::escape('Kojo'))->toBe('Kojo')
        ->and(CsvCell::escape(''))->toBe('')
        ->and(CsvCell::escape(null))->toBeNull()
        ->and(CsvCell::escape(12))->toBe(12)
        ->and(CsvCell::row(['a', '=b']))->toBe(['a', "'=b"]);
});
