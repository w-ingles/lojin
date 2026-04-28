<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'required', 'string', 'max:150'],
            'description'         => ['nullable', 'string'],
            'price'               => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock'               => ['sometimes', 'required', 'integer', 'min:0'],
            'active'              => ['boolean'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'image'               => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'O nome do produto é obrigatório.',
            'price.required'             => 'O preço é obrigatório.',
            'price.numeric'              => 'O preço deve ser um valor numérico.',
            'price.min'                  => 'O preço não pode ser negativo.',
            'stock.required'             => 'O estoque é obrigatório.',
            'stock.integer'              => 'O estoque deve ser um número inteiro.',
            'stock.min'                  => 'O estoque não pode ser negativo.',
            'product_category_id.exists' => 'Categoria não encontrada.',
            'image.image'                => 'O arquivo deve ser uma imagem (jpeg, png, gif, etc).',
            'image.max'                  => 'A imagem pode ter no máximo 2 MB.',
        ];
    }
}