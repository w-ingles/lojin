<?php
namespace App\Http\Requests\Auth;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class PreRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'cpf'        => ['required', 'string', new CpfRule(), Rule::unique('users', 'cpf')],
            'phone'      => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date', 'before:today'],
            'password'   => ['required', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'O nome completo é obrigatório.',
            'name.max'               => 'O nome pode ter no máximo 100 caracteres.',
            'email.required'         => 'O e-mail é obrigatório.',
            'email.email'            => 'Informe um e-mail válido.',
            'email.unique'           => 'Este e-mail já está cadastrado.',
            'cpf.required'           => 'O CPF é obrigatório.',
            'cpf.unique'             => 'Este CPF já está cadastrado na plataforma.',
            'phone.required'         => 'O telefone é obrigatório.',
            'phone.max'              => 'O telefone pode ter no máximo 20 caracteres.',
            'birth_date.required'    => 'A data de nascimento é obrigatória.',
            'birth_date.date'        => 'Informe uma data de nascimento válida.',
            'birth_date.before'      => 'A data de nascimento deve ser anterior a hoje.',
            'password.required'      => 'A senha é obrigatória.',
            'password.min'           => 'A senha deve ter no mínimo 8 caracteres.',
        ];
    }
}