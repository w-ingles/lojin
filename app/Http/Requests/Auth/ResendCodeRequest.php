<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;

class ResendCodeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pending_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'pending_token.required' => 'Token de verificação ausente. Reinicie o cadastro.',
        ];
    }
}