<?php
namespace App\Http\Controllers\Api\SuperAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreUniversityRequest;
use App\Http\Requests\SuperAdmin\UpdateUniversityRequest;
use App\Models\University;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(StoreUniversityRequest $request): JsonResponse
    {
        $university = University::create($request->validated());
        return response()->json($university->loadCount('tenants'), 201);
    }

    public function update(UpdateUniversityRequest $request, University $university): JsonResponse
    {
        $university->update($request->validated());
        return response()->json($university->loadCount('tenants'));
    }

    public function destroy(University $university): JsonResponse
    {
        if ($university->tenants()->exists()) {
            return response()->json(['message' => 'Esta universidade possui atléticas vinculadas e não pode ser removida.'], 422);
        }
        $university->delete();
        return response()->json(null, 204);
    }
}