<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Helper\ResponseBuilder;
use App\Models\LoginSlide;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginSlideController extends Controller
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
     * GET /api/login-slides
     * Publik — dipakai halaman Login untuk slider "Info Terkini".
     */
    public function publicIndex()
    {
        $slides = LoginSlide::active()->orderBy('order')->orderBy('id')->get();

        return ResponseBuilder::success(200, 'success', $slides);
    }

    /**
     * GET /api/admins/login-slides
     * Daftar penuh untuk tabel admin (termasuk yang nonaktif).
     */
    public function index()
    {
        $slides = LoginSlide::orderBy('order')->orderBy('id')->get();

        return ResponseBuilder::success(200, 'success', $slides);
    }

    private function rules(): array
    {
        return [
            'title'     => 'required|string|max:150',
            'body'      => 'nullable|string',
            'order'     => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'image'     => 'nullable|image|max:2048',
        ];
    }

    /**
     * POST /api/admins/login-slides
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), array_merge($this->rules(), [
            'image' => 'required|image|max:2048',
        ]));

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $data = $request->only(['title', 'body', 'order']);
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['image'] = $this->storeImage($request);

        $slide = LoginSlide::create($data);

        $this->logActivity(
            ActivityLogService::TYPE_LOGIN_SLIDE_CREATE,
            "Menambahkan slide login: {$slide->title}",
            ['login_slide_id' => $slide->id],
        );

        return ResponseBuilder::success(201, 'Slide berhasil ditambahkan.', $slide);
    }

    /**
     * POST /api/admins/login-slides/{id}
     * Pakai POST (bukan PUT) supaya upload gambar tetap jalan lewat multipart/form-data.
     */
    public function update(Request $request, $id)
    {
        $slide = LoginSlide::find($id);

        if (!$slide) {
            return response()->json([
                'status'  => 404,
                'message' => 'Slide tidak ditemukan.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $data = $request->only(['title', 'body', 'order']);
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request);
        }

        $slide->update($data);

        $this->logActivity(
            ActivityLogService::TYPE_LOGIN_SLIDE_UPDATE,
            "Memperbarui slide login: {$slide->title}",
            ['login_slide_id' => $slide->id],
        );

        return ResponseBuilder::success(200, 'Slide berhasil diperbarui.', $slide->fresh());
    }

    /**
     * DELETE /api/admins/login-slides/{id}
     */
    public function destroy($id)
    {
        $slide = LoginSlide::find($id);

        if (!$slide) {
            return response()->json([
                'status'  => 404,
                'message' => 'Slide tidak ditemukan.',
                'data'    => [],
            ], 404);
        }

        $title = $slide->title;
        $slide->delete();

        $this->logActivity(
            ActivityLogService::TYPE_LOGIN_SLIDE_DELETE,
            "Menghapus slide login: {$title}",
            ['login_slide_id' => $id],
        );

        return response()->json([
            'status'  => 200,
            'message' => 'Slide berhasil dihapus.',
            'data'    => [],
        ], 200);
    }

    private function storeImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = 'login_slide_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = public_path('storage/login-slides');
        if (!file_exists($path)) mkdir($path, 0755, true);
        $file->move($path, $filename);

        return rtrim(config('app.url'), '/') . '/eportal-api/storage/login-slides/' . $filename;
    }
}
