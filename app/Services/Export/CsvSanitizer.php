<?php

namespace App\Services\Export;

/**
 * Neutralises spreadsheet formulas in exported CSV cells.
 *
 * Excel and LibreOffice evaluate a cell beginning with =, +, -, @, a tab or a
 * carriage return even when the file was written by someone else. Farm names,
 * addresses and producer names are producer-controlled, so an export opened by
 * an admin must not be able to execute a formula.
 */
final class CsvSanitizer
{
    /**
     * Prefix a dangerous leading character with an apostrophe.
     *
     * Excel treats the apostrophe as text qualifier and hides it from the
     * rendered cell, so the displayed value is unchanged.
     */
    public static function cell(mixed $value): string
    {
        $cell = (string) $value;

        if ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$cell;
        }

        return $cell;
    }
}
