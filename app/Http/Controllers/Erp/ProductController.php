<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const SORTS = [
        'recent' => ['created_at', 'desc'],
        'name' => ['name', 'asc'],
        'price_asc' => ['sale_price', 'asc'],
        'price_desc' => ['sale_price', 'desc'],
        'stock' => ['stock', 'asc'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'brand', 'status', 'stock', 'channel', 'sort']);
        [$column, $direction] = self::SORTS[$filters['sort'] ?? 'recent'] ?? self::SORTS['recent'];

        $products = Product::query()
            ->with(['category', 'brand'])
            ->filter($filters)
            ->orderBy($column, $direction)
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Product::count(),
            'published' => Product::where('is_published', true)->count(),
            'low_stock' => Product::lowStock()->count(),
            'out_of_stock' => Product::outOfStock()->count(),
            'inventory_value' => (float) Product::where('stock', '>', 0)->sum(DB::raw('cost_price * stock')),
        ];

        return view('erp.products.index', [
            'products' => $products,
            'stats' => $stats,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('erp.products.create', [
            'product' => new Product(['unit' => 'unidad', 'tax_rate' => 21, 'is_active' => true]),
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return redirect()
            ->route('erp.products.show', $product)
            ->with('success', "Producto \"{$product->name}\" creado.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): View
    {
        $product->load(['category', 'brand']);

        return view('erp.products.show', [
            'product' => $product,
            'movements' => $product->stockMovements()->latest()->latest('id')->take(8)->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        return view('erp.products.edit', [
            'product' => $product,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $data['image_path'] = $request->hasFile('image')
                ? $request->file('image')->store('products', 'public')
                : null;
        }

        $product->update($data);

        return redirect()
            ->route('erp.products.show', $product)
            ->with('success', 'Cambios guardados.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('erp.products.index')
            ->with('success', "Producto \"{$product->name}\" eliminado.");
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'units' => Product::UNITS,
            'taxRates' => Product::TAX_RATES,
        ];
    }
}
