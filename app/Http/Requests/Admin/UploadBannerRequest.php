<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class UploadBannerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'banner' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'banner.required' => 'Selecione uma imagem para o banner.',
            'banner.image'    => 'O arquivo deve ser uma imagem.',
            'banner.mimes'    => 'O banner deve ser JPEG, PNG ou WebP.',
            'banner.max'      => 'O banner pode ter no máximo 5 MB.',
        ];
    }
}