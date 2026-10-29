<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = Category::query()
            ->withCount('products')
            ->ordered()
            ->get();

        return view('erp.categories.index', [
            'categories' => $categories,
            'missingImages' => $categories->whereNull('image_path')->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('erp.categories.create', [
            'category' => new Category([
                'is_active' => true,
                'position' => (Category::max('position') ?? 0) + 1,
            ]),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('image');
        $data['image_path'] = $request->file('image')->store('categories', 'public');

        $category = Category::create($data);

        return redirect()
            ->route('erp.categories.index')
            ->with('success', "Categoría \"{$category->name}\" creada.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): View
    {
        $category->loadCount('products');

        return view('erp.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $data['image_path'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()
            ->route('erp.categories.index')
            ->with('success', 'Cambios guardados.');
    }

    /**
     * Remove the specified resource from storage.
     * Los productos de la categoría no se borran: quedan "sin categoría".
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();

        return redirect()
            ->route('erp.categories.index')
            ->with('success', "Categoría \"{$category->name}\" eliminada.");
    }
}
