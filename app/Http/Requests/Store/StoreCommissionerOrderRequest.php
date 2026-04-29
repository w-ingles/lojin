<?php
namespace App\Http\Requests\Store;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionerOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_user_id' => ['required', 'integer', 'exists:users,id'],
            'notes'            => ['nullable', 'string', 'max:500'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.type'     => ['required', 'in:ticket_batch,product'],
            'items.*.id'       => ['required', 'integer'],
            'items.*.qty'      => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_user_id.required' => 'Identifique o cliente pelo CPF antes de confirmar.',
            'customer_user_id.exists'   => 'Cliente não encontrado.',
            'items.required'            => 'Selecione ao menos um ingresso ou produto.',
            'items.min'                 => 'Selecione ao menos um ingresso ou produto.',
            'items.*.qty.min'           => 'A quantidade deve ser de pelo menos 1.',
        ];
    }
}