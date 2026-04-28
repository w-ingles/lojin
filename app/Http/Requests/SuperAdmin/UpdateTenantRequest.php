<?php
namespace App\Http\Requests\SuperAdmin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;
        return [
            'name'          => ['sometimes', 'required', 'string', 'max:100'],
            'slug'          => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/', Rule::unique('tenants')->ignore($tenantId)],
            'email'         => ['nullable', 'email'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'university_id' => ['required', 'exists:universities,id'],
            'description'   => ['nullable', 'string'],
            'plan'          => ['nullable', 'in:basic,premium'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'O nome da atlética é obrigatório.',
            'slug.required'          => 'O slug é obrigatório.',
            'slug.unique'            => 'Este slug já está em uso por outra atlética.',
            'slug.regex'             => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'email.email'            => 'Informe um e-mail válido.',
            'university_id.required' => 'A universidade é obrigatória.',
            'university_id.exists'   => 'Universidade não encontrada.',
            'plan.in'                => 'O plano deve ser básico ou premium.',
        ];
    }
}