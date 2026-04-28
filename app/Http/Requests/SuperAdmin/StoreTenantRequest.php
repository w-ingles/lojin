<?php
namespace App\Http\Requests\SuperAdmin;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:100'],
            'slug'           => ['required', 'string', 'max:50', 'unique:tenants,slug', 'regex:/^[a-z0-9-]+$/'],
            'email'          => ['nullable', 'email'],
            'phone'          => ['nullable', 'string', 'max:20'],
            'university_id'  => ['required', 'exists:universities,id'],
            'description'    => ['nullable', 'string'],
            'plan'           => ['nullable', 'in:basic,premium'],
            'admin_name'     => ['required', 'string', 'max:100'],
            'admin_email'    => ['required', 'email', 'unique:users,email'],
            'admin_cpf'      => ['required', 'string', new CpfRule(), Rule::unique('users', 'cpf')],
            'admin_password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'O nome da atlética é obrigatório.',
            'name.max'               => 'O nome pode ter no máximo 100 caracteres.',
            'slug.required'          => 'O slug é obrigatório.',
            'slug.unique'            => 'Este slug já está em uso por outra atlética.',
            'slug.regex'             => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'slug.max'               => 'O slug pode ter no máximo 50 caracteres.',
            'email.email'            => 'Informe um e-mail válido para a atlética.',
            'university_id.required' => 'A universidade é obrigatória.',
            'university_id.exists'   => 'Universidade não encontrada.',
            'plan.in'                => 'O plano deve ser básico ou premium.',
            'admin_name.required'    => 'O nome do administrador é obrigatório.',
            'admin_email.required'   => 'O e-mail do administrador é obrigatório.',
            'admin_email.unique'     => 'Este e-mail já está cadastrado na plataforma.',
            'admin_cpf.required'     => 'O CPF do administrador é obrigatório.',
            'admin_cpf.unique'       => 'Este CPF já está cadastrado na plataforma.',
            'admin_password.required'=> 'A senha do administrador é obrigatória.',
            'admin_password.min'     => 'A senha deve ter no mínimo 8 caracteres.',
        ];
    }
}