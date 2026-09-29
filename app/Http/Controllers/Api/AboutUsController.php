<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\AboutUsContributor;
use App\Models\AboutUsSetting;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AboutUsController extends Controller
{
    protected ActivityLogService $activityLog;

    public function __construct(ActivityLogService $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    private function logActivity(string $type, string $description, array $metadata = []): void
    {
        try {
            $actor = JWTAuth::user();
            $this->activityLog->log($type, $description, $actor?->user_id, $actor?->user_id, $metadata);
        } catch (\Exception $e) {
            // silent fail
        }
    }

    /**
     * GET /api/about-us
     * Halaman publik: konten + daftar kontributor yang aktif, dikelompokkan per aplikasi.
     */
    public function publicShow()
    {
        $settings = AboutUsSetting::current();

        $contributors = AboutUsContributor::with('appModule')
            ->active()
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return ResponseBuilder::success(200, 'success', [
            'settings'     => $settings,
            'contributors' => $contributors,
        ]);
    }

    /**
     * GET /api/admins/about-us
     * Ambil konten untuk diisi ulang ke form edit admin.
     */
    public function show()
    {
        return ResponseBuilder::success(200, 'success', AboutUsSetting::current());
    }

    /**
     * POST /api/admins/about-us
     * Update konten About Us. Pakai POST (bukan PUT) karena upload foto
     * banner butuh multipart/form-data, yang tidak ke-parse PHP di method PUT.
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'               => 'nullable|string|max:150',
            'description'         => 'nullable|string',
            'banner_photo'        => 'nullable|image|max:2048',
            'remove_banner_photo' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $settings = AboutUsSetting::current();

        $updateData = array_filter([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
        ], fn ($value) => $value !== null);

        if ($request->hasFile('banner_photo')) {
            $file = $request->file('banner_photo');
            $filename = 'about_us_banner_' . time() . '.' . $file->getClientOriginalExtension();
            $path = public_path('storage/about-us');
            if (!file_exists($path)) mkdir($path, 0755, true);
            $file->move($path, $filename);
            $updateData['banner_photo'] = $this->publicAssetUrl('storage/about-us/' . $filename);
        } elseif ($request->boolean('remove_banner_photo')) {
            $updateData['banner_photo'] = null;
        }

        $settings->update($updateData);

        $this->logActivity(
            ActivityLogService::TYPE_ABOUT_US_UPDATE,
            'Memperbarui konten About Us',
        );

        return ResponseBuilder::success(200, 'About Us berhasil diperbarui.', $settings->fresh());
    }

    /**
     * GET /api/admins/about-us/contributors
     * Daftar kontributor untuk tabel admin (termasuk yang nonaktif).
     */
    public function indexContributors(Request $request)
    {
        $query = AboutUsContributor::with('appModule')
            ->orderBy('order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('app_module_id')) {
            $query->where('app_module_id', $request->app_module_id);
        }

        if ($request->boolean('all')) {
            return ResponseBuilder::success(200, 'success', $query->get());
        }

        return ResponseBuilder::paginated($query->paginate($request->query('per_page', 25)));
    }

    /**
     * GET /api/admins/about-us/contributors/{id}
     */
    public function showContributor($id)
    {
        $contributor = AboutUsContributor::with('appModule')->find($id);

        if (!$contributor) {
            return response()->json([
                'status'  => 404,
                'message' => 'Kontributor tidak ditemukan.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $contributor);
    }

    private function contributorRules($id = null): array
    {
        return [
            'name'           => 'required|string|max:150',
            'type'           => 'required|in:dosen,mahasiswa',
            'angkatan'       => 'nullable|string|max:10',
            'contribution'   => 'nullable|string|max:150',
            'app_module_id'  => 'nullable|exists:app_module,id',
            'order'          => 'nullable|integer',
            'is_active'      => 'nullable|boolean',
            'photo'          => 'nullable|image|max:2048',
        ];
    }

    /**
     * POST /api/admins/about-us/contributors
     */
    public function storeContributor(Request $request)
    {
        $validator = Validator::make($request->all(), $this->contributorRules());

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $data = $request->only(['name', 'type', 'angkatan', 'contribution', 'app_module_id', 'order']);
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storeContributorPhoto($request);
        }

        $contributor = AboutUsContributor::create($data);

        $this->logActivity(
            ActivityLogService::TYPE_ABOUT_US_CONTRIBUTOR_CREATE,
            "Menambahkan kontributor About Us: {$contributor->name}",
            ['contributor_id' => $contributor->id],
        );

        return ResponseBuilder::success(201, 'Kontributor berhasil ditambahkan.', $contributor->load('appModule'));
    }

    /**
     * POST /api/admins/about-us/contributors/{id}
     * Pakai POST (bukan PUT) supaya upload foto tetap jalan lewat multipart/form-data.
     */
    public function updateContributor(Request $request, $id)
    {
        $contributor = AboutUsContributor::find($id);

        if (!$contributor) {
            return response()->json([
                'status'  => 404,
                'message' => 'Kontributor tidak ditemukan.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), $this->contributorRules($id));

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $data = $request->only(['name', 'type', 'angkatan', 'contribution', 'app_module_id', 'order']);
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storeContributorPhoto($request);
        }

        $contributor->update($data);

        $this->logActivity(
            ActivityLogService::TYPE_ABOUT_US_CONTRIBUTOR_UPDATE,
            "Memperbarui kontributor About Us: {$contributor->name}",
            ['contributor_id' => $contributor->id],
        );

        return ResponseBuilder::success(200, 'Kontributor berhasil diperbarui.', $contributor->fresh('appModule'));
    }

    private function storeContributorPhoto(Request $request): string
    {
        $file = $request->file('photo');
        $filename = 'about_us_contributor_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = public_path('storage/about-us/contributors');
        if (!file_exists($path)) mkdir($path, 0755, true);
        $file->move($path, $filename);
        return $this->publicAssetUrl('storage/about-us/contributors/' . $filename);
    }

    /**
     * Nginx cuma proxy path di bawah `/eportal-api/` ke backend ini — path lain
     * (termasuk `/storage/...` bawaan Laravel) jatuh ke fallback SPA frontend
     * dan balik HTML, bukan file asli. Makanya URL asset di sini sengaja
     * diarahkan lewat prefix itu, ke route baru di routes/web.php yang
     * benar-benar men-stream filenya.
     */
    private function publicAssetUrl(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/eportal-api/' . ltrim($path, '/');
    }

    /**
     * DELETE /api/admins/about-us/contributors/{id}
     */
    public function destroyContributor($id)
    {
        $contributor = AboutUsContributor::find($id);

        if (!$contributor) {
            return response()->json([
                'status'  => 404,
                'message' => 'Kontributor tidak ditemukan.',
                'data'    => [],
            ], 404);
        }

        $name = $contributor->name;
        $contributor->delete();

        $this->logActivity(
            ActivityLogService::TYPE_ABOUT_US_CONTRIBUTOR_DELETE,
            "Menghapus kontributor About Us: {$name}",
            ['contributor_id' => $id],
        );

        return response()->json([
            'status'  => 200,
            'message' => 'Kontributor berhasil dihapus.',
            'data'    => [],
        ], 200);
    }
}
