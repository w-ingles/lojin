<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketBatchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'description'    => ['nullable', 'string'],
            'price'          => ['required', 'numeric', 'min:0'],
            'quantity'       => ['required', 'integer', 'min:1'],
            'is_active'      => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'available_until'=> ['nullable', 'date'],
            'max_per_order'  => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'O nome do lote é obrigatório.',
            'name.max'           => 'O nome pode ter no máximo 100 caracteres.',
            'price.required'     => 'O preço é obrigatório.',
            'price.numeric'      => 'O preço deve ser um valor numérico.',
            'price.min'          => 'O preço não pode ser negativo.',
            'quantity.required'  => 'A quantidade é obrigatória.',
            'quantity.integer'   => 'A quantidade deve ser um número inteiro.',
            'quantity.min'       => 'A quantidade deve ser de pelo menos 1.',
            'available_from.date'  => 'Informe uma data de início válida.',
            'available_until.date' => 'Informe uma data de encerramento válida.',
            'max_per_order.integer'=> 'O limite por pedido deve ser um número inteiro.',
            'max_per_order.min'    => 'O limite por pedido deve ser de pelo menos 1.',
        ];
    }
}