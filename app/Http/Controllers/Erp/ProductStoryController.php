<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductStoryImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductStoryController extends Controller
{
    /**
     * Historia de Instagram del producto (PNG 1080x1920).
     * Con ?descargar=1 el navegador la descarga en vez de mostrarla.
     */
    public function __invoke(Request $request, Product $product, ProductStoryImage $story): Response
    {
        $disposition = $request->boolean('descargar') ? 'attachment' : 'inline';

        return response($story->render($product), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "{$disposition}; filename=\"historia-{$product->slug}.png\"",
            'Cache-Control' => 'no-store',
        ]);
    }
}
