<?php

namespace App\Http\Controllers\Erp;

use App\Catalog\CatalogBuilder;
use App\Catalog\CatalogImages;
use App\Catalog\SectionType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogBuilder $builder) {}

    /**
     * Pantalla para armar el catálogo: portada, categorías y secciones futuras.
     */
    public function index(): View
    {
        return view('erp.catalog.index', [
            'categories' => $this->builder->availableCategories(),
            'uncategorized' => CatalogBuilder::productsQuery(Product::query())->whereNull('category_id')->count(),
            'addableSections' => SectionType::addable(),
            'defaultSubtitle' => ucfirst(now()->translatedFormat('F Y')),
        ]);
    }

    /**
     * Genera el PDF. Con ?preview=1 se abre en el navegador en vez de descargarse.
     */
    public function pdf(Request $request, CatalogImages $images): Response|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:40'],
            'subtitle' => ['nullable', 'string', 'max:40'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ], [
            'title.required' => 'Poné un título para la portada.',
            'max' => 'El texto de la portada es demasiado largo (máximo :max letras).',
            'categories.required' => 'Elegí al menos una categoría.',
        ]);

        $sections = $this->builder->build($data['title'], $data['subtitle'] ?? null, $data['categories']);

        if (CatalogBuilder::productCount($sections) === 0) {
            return back()->withInput()->withErrors(['categories' => 'Las categorías elegidas no tienen productos con stock.']);
        }

        set_time_limit(300);   // catálogos grandes: muchas fotos para procesar

        $pdf = Pdf::loadView('catalog.pdf', ['sections' => $sections, 'images' => $images])
            ->setPaper('a4')
            ->setOption(['chroot' => base_path()]);

        $filename = 'catalogo-'.now()->format('Y-m-d').'.pdf';

        return $request->boolean('preview') ? $pdf->stream($filename) : $pdf->download($filename);
    }
}
