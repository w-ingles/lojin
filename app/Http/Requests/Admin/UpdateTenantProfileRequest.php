<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'email'       => ['nullable', 'email'],
            'phone'       => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da atlética é obrigatório.',
            'name.max'      => 'O nome pode ter no máximo 100 caracteres.',
            'email.email'   => 'Informe um e-mail válido.',
        ];
    }
}