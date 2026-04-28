<?php
namespace App\Http\Requests\SuperAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $universityId = $this->route('university')?->id;
        return [
            'name'      => ['sometimes', 'required', 'string', 'max:150', Rule::unique('universities')->ignore($universityId)],
            'acronym'   => ['nullable', 'string', 'max:20'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da universidade é obrigatório.',
            'name.max'      => 'O nome pode ter no máximo 150 caracteres.',
            'name.unique'   => 'Já existe uma universidade com este nome.',
            'acronym.max'   => 'A sigla pode ter no máximo 20 caracteres.',
            'state.size'    => 'O estado deve ter exatamente 2 letras (ex: SP).',
        ];
    }
}