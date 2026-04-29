<?php
namespace App\Http\Controllers\Api\Store;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\UpdateUserProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'phone'             => $user->phone,
            'cpf'               => $user->cpf
                ? substr($user->cpf, 0, 3) . '.***.***-' . substr($user->cpf, -2)
                : null,
            'birth_date'        => $user->birth_date,
            'is_complete'       => $user->isProfileComplete(),
            'campos_faltando'   => $user->profileMissingFields(),
        ]);
    }

    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['cpf'])) {
            $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        }

        $user->update($data);

        return response()->json([
            'id'              => $user->fresh()->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'is_complete'     => $user->isProfileComplete(),
            'campos_faltando' => $user->profileMissingFields(),
        ]);
    }
}