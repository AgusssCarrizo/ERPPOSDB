<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erp\StoreSettingRequest;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StoreSettingController extends Controller
{
    /**
     * Textos e imágenes de la tienda online.
     */
    public function edit(): View
    {
        return view('erp.store.edit', [
            'settings' => StoreSetting::current(),
            'featuredCount' => Product::availableInStore()->where('is_featured', true)->count(),
            'publishedCount' => Product::availableInStore()->count(),
            // Para la promoción se puede elegir cualquier producto publicado (si no tiene stock, el bloque no se muestra).
            'promoProducts' => Product::publishedInStore()->orderBy('name')->get(['id', 'name', 'stock']),
        ]);
    }

    public function update(StoreSettingRequest $request, ImageStorage $images): RedirectResponse
    {
        $settings = StoreSetting::current();
        $data = $request->safe()->except(['hero_image', 'remove_hero_image']);

        if ($request->hasFile('hero_image')) {
            $data['hero_image_path'] = $images->replace($request->file('hero_image'), 'store', 'hero_image', $settings->hero_image_path);
        } elseif ($request->boolean('remove_hero_image')) {
            $images->delete($settings->hero_image_path);
            $data['hero_image_path'] = null;
        }

        $settings->update($data);

        return redirect()
            ->route('erp.store.edit')
            ->with('success', 'Tienda actualizada.');
    }
}
