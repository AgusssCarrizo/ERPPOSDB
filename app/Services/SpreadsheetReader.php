<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lee planillas .csv y .xlsx sin librerías externas.
 * Devuelve las filas como arrays con los encabezados normalizados ("Stock mínimo" -> "stock_minimo").
 */
class SpreadsheetReader
{
    /**
     * @return list<array{line: int, values: array<string, string>}> line = número de fila en la planilla
     */
    public function read(string $path, string $extension): array
    {
        $rows = strtolower($extension) === 'xlsx' ? $this->xlsxRows($path) : $this->csvRows($path);

        $headerIndex = array_key_first(array_filter($rows, fn (array $row) => $this->isFilled($row)));

        if ($headerIndex === null) {
            return [];
        }

        $headers = array_map(fn ($header) => Str::slug((string) $header, '_'), $rows[$headerIndex]);
        $result = [];

        foreach ($rows as $index => $row) {
            if ($index <= $headerIndex || ! $this->isFilled($row)) {
                continue;
            }

            $values = [];
            foreach ($headers as $column => $header) {
                if ($header !== '') {
                    $values[$header] = trim((string) ($row[$column] ?? ''));
                }
            }

            $result[] = ['line' => $index + 1, 'values' => $values];
        }

        return $result;
    }

    private function isFilled(array $row): bool
    {
        return collect($row)->contains(fn ($value) => trim((string) $value) !== '');
    }

    /**
     * CSV de Excel: puede venir separado por ";" (Excel en español) o ",", y en UTF-8 o Windows-1252.
     *
     * @return array<int, list<string>>
     */
    private function csvRows(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);   // marca BOM de "CSV UTF-8"

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');   // "CSV (delimitado por comas)" viejo
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = collect([';', ',', "\t"])->sortByDesc(fn ($candidate) => substr_count($firstLine, $candidate))->first();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $rows = [];
        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $rows[] = $row === [null] ? [] : $row;
        }
        fclose($stream);

        return $rows;
    }

    /**
     * Un .xlsx es un zip con XML adentro. Se lee la primera hoja.
     *
     * @return array<int, list<string>>
     */
    private function xlsxRows(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo de Excel.');
        }

        // Los textos se guardan una sola vez en sharedStrings y las celdas los referencian por número.
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach (simplexml_load_string($xml)->si as $item) {
                $shared[] = $this->text($item);
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheet === false) {
            throw new RuntimeException('El archivo de Excel no tiene hojas.');
        }

        $rows = [];
        foreach (simplexml_load_string($sheet)->sheetData->row as $row) {
            $values = [];

            foreach ($row->c as $cell) {
                // "C5" -> columna 2 (las celdas vacías no vienen en el XML)
                $column = $this->columnIndex(preg_replace('/\d+/', '', (string) $cell['r']));

                $values[$column] = match ((string) $cell['t']) {
                    's' => $shared[(int) $cell->v] ?? '',
                    'inlineStr' => $this->text($cell->is),
                    'b' => (string) $cell->v === '1' ? 'si' : 'no',
                    default => (string) $cell->v,
                };
            }

            $filled = $values === [] ? [] : array_replace(array_fill(0, max(array_keys($values)) + 1, ''), $values);
            $rows[(int) $row['r'] - 1] = $filled;
        }

        ksort($rows);

        return $rows;
    }

    /**
     * Texto de una celda: simple (<t>) o con partes de distinto formato (<r><t>).
     */
    private function text(SimpleXMLElement $node): string
    {
        if (isset($node->t)) {
            return (string) $node->t;
        }

        $text = '';
        foreach ($node->r as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    /**
     * "A" -> 0, "Z" -> 25, "AA" -> 26
     */
    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
