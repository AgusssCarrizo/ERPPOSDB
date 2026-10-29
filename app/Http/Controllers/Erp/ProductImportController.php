<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\ProductImport;
use App\Services\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Importar productos desde Excel/CSV en dos pasos: subir y revisar -> confirmar.
 * Entre un paso y otro el archivo queda en storage/app/private/imports.
 */
class ProductImportController extends Controller
{
    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly ProductImport $import,
    ) {}

    public function create(): View
    {
        return view('erp.products.import');
    }

    /**
     * Planilla de ejemplo para completar. Separada por ";" para que Excel en español la abra en columnas.
     */
    public function template(): StreamedResponse
    {
        $rows = [
            ['nombre', 'categoria', 'sku', 'costo', 'margen', 'precio', 'iva', 'stock', 'stock_minimo', 'unidad', 'descripcion', 'activo', 'tienda'],
            ['Mate de cerámica', 'Cocina', '', '3000', '', '6000', '21', '12', '3', 'unidad', 'Mate de cerámica esmaltada', 'si', 'no'],
            ['Set de vasos x6', 'Cocina', 'VAS-006', '8000', '60', '', '21', '5', '2', 'pack', '', 'si', 'si'],
        ];

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");   // BOM: así Excel respeta las tildes
            foreach ($rows as $row) {
                fputcsv($output, $row, ';', '"', '');
            }
            fclose($output);
        }, 'plantilla-productos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Paso 1: sube la planilla y muestra cómo va a quedar cada producto, sin guardar nada.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate(
            ['file' => ['required', 'file', 'extensions:csv,txt,xlsx', 'max:5120']],
            [
                'file.required' => 'Elegí el archivo.',
                'file.extensions' => 'El archivo tiene que ser .xlsx (Excel) o .csv.',
                'file.max' => 'El archivo no puede superar los 5 MB.',
            ],
        );

        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        $token = Str::uuid().'.'.$extension;
        $request->file('file')->storeAs('imports', $token, 'local');

        try {
            $rows = $this->rows($token);
        } catch (RuntimeException) {
            $rows = [];
        }

        if ($rows === []) {
            return back()->with('error', 'La planilla está vacía o no se pudo leer.');
        }

        if (count($rows) > ProductImport::MAX_ROWS) {
            return back()->with('error', 'La planilla tiene '.count($rows).' filas: el máximo por vez es '.ProductImport::MAX_ROWS.'. Dividila en partes.');
        }

        $checked = $this->import->check($rows);

        return view('erp.products.import-preview', [
            'token' => $token,
            'rows' => $checked,
            'errorCount' => collect($checked)->filter(fn ($row) => $row['errors'] !== [])->count(),
            'newCategories' => collect($checked)->filter(fn ($row) => $row['data']['new_category'])->pluck('data.category')->unique(fn ($name) => mb_strtolower($name))->values(),
            'unknownColumns' => $this->import->unknownColumns($rows),
        ]);
    }

    /**
     * Paso 2: importa lo que se revisó. Se vuelve a validar por si algo cambió en el medio.
     */
    public function store(Request $request): RedirectResponse
    {
        $token = $request->string('token')->toString();
        $path = 'imports/'.$token;

        if (! preg_match('/^[0-9a-f-]{36}\.(csv|txt|xlsx)$/', $token) || ! Storage::disk('local')->exists($path)) {
            return to_route('erp.products.import')->with('error', 'No encontré la planilla: subila de nuevo.');
        }

        $checked = $this->import->check($this->rows($token));

        if (collect($checked)->contains(fn ($row) => $row['errors'] !== [])) {
            return to_route('erp.products.import')->with('error', 'La planilla tiene errores: corregila y subila de nuevo.');
        }

        $result = $this->import->import($checked);
        Storage::disk('local')->delete($path);

        $message = "Se importaron {$result['products']} productos.";
        if ($result['categories'] !== []) {
            $message .= ' Categorías nuevas: '.implode(', ', $result['categories']).' (cargales la foto de portada para el catálogo).';
        }

        return to_route('erp.products.index')->with('success', $message);
    }

    /**
     * @return list<array{line: int, values: array<string, string>}>
     */
    private function rows(string $token): array
    {
        return $this->reader->read(Storage::disk('local')->path('imports/'.$token), pathinfo($token, PATHINFO_EXTENSION));
    }
}
