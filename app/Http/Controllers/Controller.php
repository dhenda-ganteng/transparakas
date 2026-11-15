<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Mencegah CSV Formula Injection dengan memberi awalan tanda petik tunggal
     * pada sel teks yang diawali karakter formula (=, +, -, @, tab, atau CR).
     */
    protected function sanitizeCsvRow(array $row): array
    {
        return array_map(function (mixed $cell): mixed {
            if (!is_string($cell) || $cell === '') {
                return $cell;
            }

            if (preg_match('/^[\t\r ]*[=+\-@]/', $cell) === 1) {
                return "'" . $cell;
            }

            return $cell;
        }, $row);
    }
}
