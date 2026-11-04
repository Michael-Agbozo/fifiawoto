<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Writes a one-sheet .xlsx file. Text is always stored as text, never as a
 * formula, so a value like "=HYPERLINK(...)" from a public form can't run in Excel.
 */
class SimpleXlsx
{
    /**
     * @param  array<int, array<string, mixed>>  $rows  Rows keyed by column heading.
     * @return string Path to a temporary file; the caller deletes it.
     */
    public static function write(string $sheetName, array $rows): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Excel file.');
        }

        $table = $rows === [] ? [['No rows in selected range']] : [array_keys($rows[0]), ...array_map('array_values', $rows)];

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.self::escape(self::sheetTitle($sheetName)).'" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($table));
        $zip->close();

        return $path;
    }

    /** @param array<int, array<int, mixed>> $table */
    private static function sheetXml(array $table): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($table as $row) {
            $xml .= '<row>';
            foreach ($row as $value) {
                $xml .= is_int($value) || is_float($value)
                    ? '<c><v>'.$value.'</v></c>'
                    : '<c t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function escape(string $value): string
    {
        // Control characters are illegal in XML and would make Excel call the file corrupt.
        return htmlspecialchars((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Excel limits sheet names to 31 characters and bans a few symbols. */
    private static function sheetTitle(string $name): string
    {
        return mb_substr((string) preg_replace('#[\\\\/?*\[\]:]#', ' ', $name), 0, 31) ?: 'Report';
    }
}
