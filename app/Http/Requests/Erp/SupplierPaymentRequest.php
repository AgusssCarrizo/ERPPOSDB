<?php

namespace App\Http\Requests\Erp;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierPaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'amount.min' => 'El importe tiene que ser mayor a cero.',
            'numeric' => 'El importe tiene que ser un número.',
            'date.before_or_equal' => 'La fecha no puede ser futura.',
            'max' => 'El campo :attribute es demasiado largo.',
        ];
    }

    public function attributes(): array
    {
        return ['amount' => 'importe', 'method' => 'medio de pago', 'date' => 'fecha', 'note' => 'nota'];
    }
}
