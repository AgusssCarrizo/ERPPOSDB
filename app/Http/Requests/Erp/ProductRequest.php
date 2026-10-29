<?php

namespace App\Http\Requests\Erp;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('products')->ignore($product)],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products')->ignore($product)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit' => ['required', Rule::in(array_keys(Product::UNITS))],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            // Solo al crear: después el stock se mueve desde Inventario.
            'stock' => $product ? ['prohibited'] : ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['boolean'],
            'is_active' => ['boolean'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'unique' => 'Ya existe un producto con ese :attribute.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute no puede ser menor a :min.',
            'max' => 'El campo :attribute es demasiado largo.',
            'image.max' => 'La imagen no puede superar los 2 MB.',
            'stock.prohibited' => 'El stock se modifica desde Inventario → Ajustar stock.',
            'tax_rate.max' => 'El IVA no puede ser mayor a 100%.',
            'image' => 'El archivo debe ser una imagen.',
            'exists' => 'El valor seleccionado en :attribute no es válido.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'alpha_dash' => 'El :attribute solo puede tener letras, números y guiones.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'sku' => 'SKU',
            'barcode' => 'código de barras',
            'category_id' => 'categoría',
            'brand_id' => 'marca',
            'description' => 'descripción',
            'unit' => 'unidad',
            'cost_price' => 'costo',
            'sale_price' => 'precio de venta',
            'tax_rate' => 'IVA',
            'compare_price' => 'precio anterior',
            'min_stock' => 'stock mínimo',
            'image' => 'imagen',
        ];
    }
}
