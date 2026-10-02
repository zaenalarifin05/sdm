<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExport
{
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_map([self::class, 'safeCell'], $headers));

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'safeCell'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function safeCell(mixed $value): string|int|float|null
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        $first = $value[0];

        if (in_array($first, ['=', '+', '-', '@'], true)
            || str_starts_with($value, "\t")
            || str_starts_with($value, "\r")) {
            return "'".$value;
        }

        return $value;
    }
}
