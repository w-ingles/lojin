<?php
namespace App\Http\Requests\Store;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'phone'      => ['required', 'string', 'max:20'],
            'cpf'        => ['nullable', 'string', new CpfRule(), Rule::unique('users', 'cpf')->ignore($this->user()->id)],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'O nome é obrigatório.',
            'phone.required'   => 'O telefone é obrigatório.',
            'cpf.unique'       => 'Este CPF já está cadastrado em outra conta.',
            'birth_date.before'=> 'A data de nascimento deve ser anterior a hoje.',
        ];
    }
}