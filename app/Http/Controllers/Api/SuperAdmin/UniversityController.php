<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UniversityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = University::withCount('tenants')
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', '%' . $request->string('search') . '%')
                  ->orWhere('acronym', 'like', '%' . $request->string('search') . '%')
            )
            ->when($request->filled('status'), fn ($q) =>
                $q->where('is_active', $request->boolean('status'))
            )
            ->orderBy('name');

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:150', 'unique:universities,name'],
            'acronym'   => ['nullable', 'string', 'max:20'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'O nome da universidade é obrigatório.',
            'name.unique'   => 'Já existe uma universidade com este nome.',
            'name.max'      => 'O nome pode ter no máximo 150 caracteres.',
            'acronym.max'   => 'A sigla pode ter no máximo 20 caracteres.',
            'state.size'    => 'O estado deve ter exatamente 2 letras (ex: SP).',
        ]);

        $university = University::create($data);

        return response()->json($university->loadCount('tenants'), 201);
    }

    public function update(Request $request, University $university): JsonResponse
    {
        $data = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:150', Rule::unique('universities')->ignore($university->id)],
            'acronym'   => ['nullable', 'string', 'max:20'],
            'city'      => ['nullable', 'string', 'max:100'],
            'state'     => ['nullable', 'string', 'size:2'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'O nome da universidade é obrigatório.',
            'name.unique'   => 'Já existe uma universidade com este nome.',
            'name.max'      => 'O nome pode ter no máximo 150 caracteres.',
            'state.size'    => 'O estado deve ter exatamente 2 letras (ex: SP).',
        ]);

        $university->update($data);

        return response()->json($university->loadCount('tenants'));
    }

    public function destroy(University $university): JsonResponse
    {
        if ($university->tenants()->exists()) {
            return response()->json([
                'message' => 'Esta universidade possui atléticas vinculadas e não pode ser removida.',
            ], 422);
        }

        $university->delete();

        return response()->json(null, 204);
    }
}
