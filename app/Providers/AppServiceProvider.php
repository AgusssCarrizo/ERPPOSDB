<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Todas las páginas de la tienda usan la configuración (anuncio, contacto) y las categorías del pie.
        View::composer('components.layouts.shop', function ($view) {
            $view->with([
                'store' => StoreSetting::current(),
                'menuCategories' => Category::inStore()->ordered()->get(['id', 'name', 'slug']),
            ]);
        });
    }
}
