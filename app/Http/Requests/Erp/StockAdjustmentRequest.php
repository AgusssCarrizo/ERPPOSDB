<?php

namespace App\Http\Requests\Erp;

use App\Enums\AdjustmentReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockAdjustmentRequest extends FormRequest
{
    /** Modos del formulario: sumar, restar o fijar la cantidad contada. */
    public const MODES = ['add' => 'Sumar', 'remove' => 'Restar', 'count' => 'Conteo'];

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
        $isCount = $this->input('mode') === 'count';

        return [
            'product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'mode' => ['required', Rule::in(array_keys(self::MODES))],
            'quantity' => ['required', 'integer', $isCount ? 'min:0' : 'min:1', 'max:1000000'],
            'reason' => [Rule::requiredIf(! $isCount), 'nullable', Rule::enum(AdjustmentReason::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'return' => ['nullable', Rule::in(['inventory'])],
        ];
    }

    /**
     * El motivo tiene que corresponder al modo (ej. "Rotura" no puede sumar).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $reason = AdjustmentReason::tryFrom((string) $this->input('reason'));
                $direction = ['add' => 'in', 'remove' => 'out'][$this->input('mode')] ?? null;

                if ($reason && $direction && ! $reason->allows($direction)) {
                    $validator->errors()->add('reason', "\"{$reason->label()}\" no corresponde a ".($direction === 'in' ? 'sumar' : 'restar').' stock.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'Elegí un producto de la lista.',
            'integer' => 'La cantidad tiene que ser un número entero.',
            'quantity.min' => 'La cantidad tiene que ser al menos :min.',
            'max' => 'El campo :attribute es demasiado largo.',
            'quantity.max' => 'La cantidad es demasiado grande.',
            'enum' => 'Elegí un motivo de la lista.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'quantity' => 'cantidad',
            'reason' => 'motivo',
            'note' => 'nota',
        ];
    }
}
