<?php

namespace App\Http\Requests\Erp;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($category)],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('categories')->ignore($category)],
            // La foto es obligatoria al crear: es la portada de la categoría en el catálogo.
            'image' => [Rule::requiredIf(! $category), 'nullable', 'image', 'max:4096'],
            'position' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'image.required' => 'Cargá una foto: se usa como portada de la categoría en el catálogo.',
            'unique' => 'Ya existe una categoría con ese :attribute.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute no puede ser menor a :min.',
            'max' => 'El campo :attribute es demasiado largo.',
            'image.max' => 'La foto no puede superar los 4 MB.',
            'image' => 'El archivo debe ser una imagen.',
            'alpha_dash' => 'El :attribute solo puede tener letras, números y guiones.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'image' => 'foto',
            'position' => 'orden',
        ];
    }
}
