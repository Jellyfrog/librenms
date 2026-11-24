<?php

namespace App\Http\Controllers\Traits;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait StreamsCsv
{
    /**
     * Stream rows as a CSV download, rows are written as they are produced: pass $query->lazy()->map(...) for large tables
     *
     * @param  string  $name  file name without the date and extension
     * @param  list<string>  $headers
     * @param  iterable<array<scalar|null>>  $rows
     */
    protected function streamCsv(string $name, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // utf-8 BOM for spreadsheet programs
            fputcsv($output, $headers, ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($output, $row, ',', '"', '\\');
            }
            fclose($output);
        }, $name . '-' . date('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
