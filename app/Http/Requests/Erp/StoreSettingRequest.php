<?php

namespace App\Http\Requests\Erp;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
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
        return [
            'announcement' => ['nullable', 'string', 'max:160'],

            'hero_title' => ['required', 'string', 'max:60'],
            'hero_highlight' => ['nullable', 'string', 'max:60'],
            'hero_text' => ['nullable', 'string', 'max:300'],
            'hero_button_label' => ['required', 'string', 'max:30'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'remove_hero_image' => ['boolean'],

            'promo_product_id' => ['nullable', 'exists:products,id'],
            'promo_eyebrow' => ['nullable', 'string', 'max:60'],
            'promo_title' => ['nullable', 'string', 'max:60'],
            'promo_highlight' => ['nullable', 'string', 'max:60'],
            'promo_text' => ['nullable', 'string', 'max:300'],
            'promo_button_label' => ['required', 'string', 'max:30'],
            'promo_badge_title' => ['nullable', 'string', 'max:30'],
            'promo_badge_text' => ['nullable', 'string', 'max:120'],

            'benefits' => ['required', 'array', 'size:3'],
            'benefits.*.title' => ['required', 'string', 'max:40'],
            'benefits.*.text' => ['required', 'string', 'max:120'],

            'about_text' => ['nullable', 'string', 'max:300'],
            'address' => ['nullable', 'string', 'max:120'],
            'instagram' => ['nullable', 'string', 'max:60', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9 +()-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'max' => 'El campo :attribute es demasiado largo.',
            'hero_image.max' => 'La imagen no puede superar los 4 MB.',
            'image' => 'El archivo debe ser una imagen.',
            'exists' => 'El producto elegido no existe.',
            'instagram.regex' => 'Escribí solo el usuario de Instagram (ej. decobazar.db).',
            'whatsapp.regex' => 'El WhatsApp solo puede tener números, espacios y +.',
        ];
    }

    public function attributes(): array
    {
        return [
            'announcement' => 'barra de anuncios',
            'hero_title' => 'título',
            'hero_highlight' => 'palabra destacada',
            'hero_text' => 'texto',
            'hero_button_label' => 'texto del botón',
            'hero_image' => 'imagen',
            'promo_product_id' => 'producto',
            'promo_eyebrow' => 'antetítulo',
            'promo_title' => 'título',
            'promo_highlight' => 'palabra destacada',
            'promo_text' => 'texto',
            'promo_button_label' => 'texto del botón',
            'promo_badge_title' => 'título de la etiqueta',
            'promo_badge_text' => 'texto de la etiqueta',
            'benefits.*.title' => 'título del beneficio',
            'benefits.*.text' => 'texto del beneficio',
            'about_text' => 'descripción',
            'address' => 'ubicación',
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
        ];
    }
}
