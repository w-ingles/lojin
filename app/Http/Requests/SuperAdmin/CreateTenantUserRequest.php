<?php
namespace App\Http\Requests\SuperAdmin;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTenantUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'cpf'      => ['required', 'string', new CpfRule(), Rule::unique('users', 'cpf')],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'in:admin,user'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'O nome é obrigatório.',
            'email.required'    => 'O e-mail é obrigatório.',
            'email.email'       => 'Informe um e-mail válido.',
            'email.unique'      => 'Este e-mail já está cadastrado.',
            'cpf.required'      => 'O CPF é obrigatório.',
            'cpf.unique'        => 'Este CPF já está cadastrado na plataforma.',
            'password.required' => 'A senha é obrigatória.',
            'password.min'      => 'A senha deve ter no mínimo 8 caracteres.',
            'role.required'     => 'O perfil do usuário é obrigatório.',
            'role.in'           => 'O perfil deve ser Admin ou Usuário.',
        ];
    }
}