<?php
namespace App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UploadBannerRequest;
use App\Http\Requests\Admin\UploadLogoRequest;
use App\Http\Requests\Admin\UpdateTenantProfileRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TenantProfileController extends Controller
{
    private function tenant(): Tenant
    {
        return app('current_tenant');
    }

    public function show(): JsonResponse
    {
        return response()->json($this->tenant());
    }

    public function update(UpdateTenantProfileRequest $request): JsonResponse
    {
        $tenant = $this->tenant();
        $tenant->update($request->validated());
        return response()->json($tenant->fresh());
    }

    public function uploadBanner(UploadBannerRequest $request): JsonResponse
    {
        $tenant = $this->tenant();

        if ($tenant->banner) {
            Storage::disk('public')->delete($tenant->banner);
        }

        $path = $request->file('banner')->store('tenants/banners', 'public');
        $tenant->update(['banner' => $path]);

        return response()->json($tenant->fresh());
    }

    public function removeBanner(): JsonResponse
    {
        $tenant = $this->tenant();

        if ($tenant->banner) {
            Storage::disk('public')->delete($tenant->banner);
            $tenant->update(['banner' => null]);
        }

        return response()->json($tenant->fresh());
    }

    public function uploadLogo(UploadLogoRequest $request): JsonResponse
    {
        $tenant = $this->tenant();

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        $path = $request->file('logo')->store('tenants/logos', 'public');
        $tenant->update(['logo' => $path]);

        return response()->json($tenant->fresh());
    }

    public function removeLogo(): JsonResponse
    {
        $tenant = $this->tenant();

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
            $tenant->update(['logo' => null]);
        }

        return response()->json($tenant->fresh());
    }
}