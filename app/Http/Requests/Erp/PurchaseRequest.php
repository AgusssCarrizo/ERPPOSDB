<?php

namespace App\Http\Requests\Erp;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PurchaseRequest extends FormRequest
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
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'paid_now' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => [Rule::requiredIf(fn () => (float) $this->input('paid_now') > 0), 'nullable', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * No se puede pagar en el momento más que el total de la compra.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if ((float) $this->input('paid_now') > $this->total() + 0.001) {
                    $validator->errors()->add('paid_now', 'No podés pagar más que el total de la compra ('.money($this->total()).').');
                }
            },
        ];
    }

    public function total(): float
    {
        $subtotal = collect($this->input('items', []))->sum(fn ($item) => (int) $item['quantity'] * (float) $item['unit_cost']);

        return round($subtotal + (float) $this->input('tax_amount'), 2);
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'items.required' => 'Agregá al menos un producto.',
            'items.min' => 'Agregá al menos un producto.',
            'items.*.product_id.required' => 'Elegí el producto.',
            'items.*.product_id.distinct' => 'Este producto está repetido: sumá las cantidades en una sola línea.',
            'items.*.product_id.exists' => 'Elegí un producto de la lista.',
            'items.*.quantity.min' => 'La cantidad tiene que ser al menos 1.',
            'items.*.quantity.integer' => 'La cantidad tiene que ser un número entero.',
            'numeric' => 'El campo :attribute tiene que ser un número.',
            'min' => 'El campo :attribute no puede ser negativo.',
            'date.before_or_equal' => 'La fecha no puede ser futura.',
            'payment_method.required' => 'Elegí cómo pagaste.',
            'max' => 'El campo :attribute es demasiado largo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'date' => 'fecha',
            'invoice_number' => 'número de factura',
            'items.*.quantity' => 'cantidad',
            'items.*.unit_cost' => 'costo',
            'tax_amount' => 'IVA / otros',
            'paid_now' => 'pagado ahora',
            'payment_method' => 'medio de pago',
            'notes' => 'notas',
        ];
    }
}
