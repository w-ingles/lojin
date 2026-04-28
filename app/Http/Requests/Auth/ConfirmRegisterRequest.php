<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pending_token' => ['required', 'string'],
            'code'          => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'pending_token.required' => 'Token de verificação ausente. Reinicie o cadastro.',
            'code.required'          => 'Informe o código de verificação.',
            'code.size'              => 'O código deve ter exatamente 6 dígitos.',
        ];
    }
}