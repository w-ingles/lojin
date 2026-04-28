<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'required', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'price'          => ['sometimes', 'required', 'numeric', 'min:0'],
            'quantity'       => ['sometimes', 'required', 'integer'],
            'is_active'      => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'available_until'=> ['nullable', 'date'],
            'max_per_order'  => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'O nome do lote é obrigatório.',
            'price.required'   => 'O preço é obrigatório.',
            'price.numeric'    => 'O preço deve ser um valor numérico.',
            'price.min'        => 'O preço não pode ser negativo.',
            'quantity.required'=> 'A quantidade é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'max_per_order.min'=> 'O limite por pedido deve ser de pelo menos 1.',
        ];
    }
}