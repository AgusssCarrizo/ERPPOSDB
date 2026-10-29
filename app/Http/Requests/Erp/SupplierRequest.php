<?php

namespace App\Http\Requests\Erp;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'cuit' => ['nullable', 'regex:/^\d{2}-\d{8}-\d$/', Rule::unique('suppliers')->ignore($this->route('supplier'))],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * El CUIT se guarda siempre con guiones (30-12345678-9), aunque lo escriban sin ellos.
     */
    protected function prepareForValidation(): void
    {
        $digits = preg_replace('/\D/', '', (string) $this->input('cuit'));

        if (strlen($digits) === 11) {
            $this->merge(['cuit' => substr($digits, 0, 2).'-'.substr($digits, 2, 8).'-'.substr($digits, 10)]);
        }
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'cuit.regex' => 'El CUIT tiene que tener 11 números (ej: 30-12345678-9).',
            'unique' => 'Ya hay un proveedor con ese :attribute.',
            'email' => 'El email no es válido.',
            'max' => 'El campo :attribute es demasiado largo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'cuit' => 'CUIT',
            'contact_name' => 'contacto',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'notes' => 'notas',
        ];
    }
}
