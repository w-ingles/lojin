<?php
namespace App\Http\Requests\Admin;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommissionerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cpf' => ['required', 'string', new CpfRule()],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.required' => 'Informe o CPF da pessoa que deseja adicionar como comissário.',
        ];
    }
}