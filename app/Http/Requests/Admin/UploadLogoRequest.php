<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Selecione uma imagem para o logo.',
            'logo.image'    => 'O arquivo deve ser uma imagem.',
            'logo.mimes'    => 'O logo deve ser JPEG, PNG ou WebP.',
            'logo.max'      => 'O logo pode ter no máximo 2 MB.',
        ];
    }
}