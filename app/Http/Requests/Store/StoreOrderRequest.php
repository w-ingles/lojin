<?php
namespace App\Http\Requests\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes'        => ['nullable', 'string', 'max:500'],
            'items'        => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:ticket_batch,product'],
            'items.*.id'   => ['required', 'integer'],
            'items.*.qty'  => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'        => 'Adicione ao menos um item ao pedido.',
            'items.min'             => 'Adicione ao menos um item ao pedido.',
            'items.*.type.required' => 'Tipo de item inválido.',
            'items.*.id.required'   => 'Identificador do item inválido.',
            'items.*.qty.required'  => 'A quantidade do item é obrigatória.',
            'items.*.qty.min'       => 'A quantidade deve ser de pelo menos 1.',
            'notes.max'             => 'As observações podem ter no máximo 500 caracteres.',
        ];
    }
}