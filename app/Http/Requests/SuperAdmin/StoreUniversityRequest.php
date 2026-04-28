<?php
namespace App\Http\Requests\SuperAdmin;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniversityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150', 'unique:universities,name'],
            'acronym'   => ['nullable', 'string', 'max:20'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da universidade é obrigatório.',
            'name.max'      => 'O nome pode ter no máximo 150 caracteres.',
            'name.unique'   => 'Já existe uma universidade com este nome.',
            'acronym.max'   => 'A sigla pode ter no máximo 20 caracteres.',
            'city.max'      => 'A cidade pode ter no máximo 100 caracteres.',
            'state.size'    => 'O estado deve ter exatamente 2 letras (ex: SP).',
        ];
    }
}