<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'location'    => ['nullable', 'string', 'max:200'],
            'address'     => ['nullable', 'string', 'max:300'],
            'starts_at'   => ['sometimes', 'required', 'date'],
            'ends_at'     => ['nullable', 'date'],
            'status'      => ['sometimes', 'required', 'in:draft,active,sold_out,finished,cancelled'],
            'minimum_age' => ['nullable', 'integer', 'min:0'],
            'banner'      => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'O nome do evento é obrigatório.',
            'name.max'           => 'O nome pode ter no máximo 200 caracteres.',
            'starts_at.required' => 'A data de início é obrigatória.',
            'starts_at.date'     => 'Informe uma data de início válida.',
            'ends_at.date'       => 'Informe uma data de encerramento válida.',
            'status.required'    => 'O status do evento é obrigatório.',
            'status.in'          => 'Status inválido.',
            'minimum_age.integer'=> 'A idade mínima deve ser um número inteiro.',
            'minimum_age.min'    => 'A idade mínima não pode ser negativa.',
            'banner.image'       => 'O banner deve ser uma imagem (jpeg, png, gif, etc).',
            'banner.max'         => 'O banner pode ter no máximo 4 MB.',
        ];
    }
}