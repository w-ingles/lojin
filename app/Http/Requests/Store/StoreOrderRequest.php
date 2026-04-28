<?php
namespace App\Http\Requests\Store;
use App\Models\Commissioner;
use App\Rules\CpfRule;
use App\Scopes\TenantScope;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    private ?Commissioner $resolvedCommissioner = null;

    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $user = auth('sanctum')->user();
        if ($user && app()->bound('current_tenant')) {
            $this->resolvedCommissioner = Commissioner::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', app('current_tenant')->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();
        }
    }

    public function rules(): array
    {
        $cpfRequired = $this->resolvedCommissioner ? 'required' : 'nullable';
        return [
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_cpf'   => [$cpfRequired, 'string', new CpfRule()],
            'notes'          => ['nullable', 'string', 'max:500'],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.type'   => ['required', 'in:ticket_batch,product'],
            'items.*.id'     => ['required', 'integer'],
            'items.*.qty'    => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'  => 'O nome do cliente é obrigatório.',
            'customer_name.max'       => 'O nome pode ter no máximo 100 caracteres.',
            'customer_email.email'    => 'Informe um e-mail válido.',
            'customer_phone.max'      => 'O telefone pode ter no máximo 20 caracteres.',
            'customer_cpf.required'   => 'O CPF do cliente é obrigatório para comissários.',
            'notes.max'               => 'As observações podem ter no máximo 500 caracteres.',
            'items.required'          => 'Adicione ao menos um item ao pedido.',
            'items.min'               => 'Adicione ao menos um item ao pedido.',
            'items.*.type.required'   => 'O tipo do item é inválido.',
            'items.*.type.in'         => 'Tipo de item não reconhecido.',
            'items.*.id.required'     => 'O identificador do item é obrigatório.',
            'items.*.qty.required'    => 'A quantidade do item é obrigatória.',
            'items.*.qty.min'         => 'A quantidade deve ser de pelo menos 1.',
        ];
    }

    public function commissioner(): ?Commissioner
    {
        return $this->resolvedCommissioner;
    }
}