<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class ValidateTicketRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'O código do ingresso é obrigatório.',
            'code.max'      => 'Código de ingresso inválido.',
        ];
    }
}