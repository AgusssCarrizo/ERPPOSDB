<?php

namespace App\Http\Requests\Pos;

use App\Enums\SalePaymentMethod;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaleRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required', 'distinct',
                Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payments' => ['required', 'array', 'min:1', 'max:4'],
            'payments.*.method' => ['required', Rule::enum(SalePaymentMethod::class)],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Controles que dependen de la base: que haya stock y que el "paga con" alcance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $items = $this->input('items');
                $products = Product::query()->findMany(array_column($items, 'product_id'))->keyBy('id');

                foreach ($items as $index => $item) {
                    $product = $products[$item['product_id']];

                    if ($product->stock < (int) $item['quantity']) {
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            match (true) {
                                $product->stock <= 0 => "{$product->name}: no hay stock.",
                                $product->stock === 1 => "{$product->name}: queda 1 en stock.",
                                default => "{$product->name}: quedan {$product->stock} en stock.",
                            },
                        );
                    }
                }

                foreach ($this->input('payments') as $index => $payment) {
                    $tendered = $payment['tendered'] ?? null;

                    if ($tendered !== null && $tendered !== '' && (float) $tendered < (float) $payment['amount']) {
                        $validator->errors()->add("payments.{$index}.tendered", 'Con lo que paga no alcanza: faltan '.money((float) $payment['amount'] - (float) $tendered).'.');
                    }
                }
            },
        ];
    }

    /**
     * Pagos listos para App\Services\Selling, con el medio de pago como enum.
     *
     * @return list<array{method: SalePaymentMethod, amount: float, tendered: ?float}>
     */
    public function payments(): array
    {
        return array_map(fn (array $payment) => [
            'method' => SalePaymentMethod::from($payment['method']),
            'amount' => round((float) $payment['amount'], 2),
            'tendered' => isset($payment['tendered']) && $payment['tendered'] !== '' ? round((float) $payment['tendered'], 2) : null,
        ], array_values($this->validated('payments')));
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Agregá al menos un producto.',
            'items.min' => 'Agregá al menos un producto.',
            'items.*.product_id.distinct' => 'Hay un producto repetido.',
            'items.*.product_id.exists' => 'Uno de los productos ya no está disponible para vender.',
            'items.*.quantity.min' => 'La cantidad tiene que ser al menos 1.',
            'items.*.quantity.integer' => 'La cantidad tiene que ser un número entero.',
            'payments.required' => 'Elegí cómo paga el cliente.',
            'payments.min' => 'Elegí cómo paga el cliente.',
            'payments.max' => 'Se puede dividir el pago en hasta 4 partes.',
            'payments.*.method.required' => 'Elegí el medio de pago.',
            'payments.*.amount.min' => 'Cada pago tiene que ser mayor a cero.',
            'required' => 'El campo :attribute es obligatorio.',
            'numeric' => 'El campo :attribute tiene que ser un número.',
            'min' => 'El campo :attribute no puede ser negativo.',
            'max' => 'El campo :attribute es demasiado largo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'items.*.quantity' => 'cantidad',
            'discount' => 'descuento',
            'payments.*.method' => 'medio de pago',
            'payments.*.amount' => 'importe',
            'payments.*.tendered' => 'paga con',
            'notes' => 'nota',
        ];
    }
}
