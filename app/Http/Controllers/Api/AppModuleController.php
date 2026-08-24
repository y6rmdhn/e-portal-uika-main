<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\AppModule;
use App\Models\SsoClient;
use App\Http\Helper\ResponseBuilder;
use App\Services\ActivityLogService;
use Tymon\JWTAuth\Facades\JWTAuth;

class AppModuleController extends Controller
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
     * GET /api/admins/app-modules
     * List all App Modules with their permissions and SSO Client credentials.
     */
    public function index(Request $request)
    {
        $query = AppModule::with(['permission', 'ssoClient']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // ?all=1 mengembalikan daftar penuh untuk selector (dialog permission).
        if ($request->boolean('all')) {
            return ResponseBuilder::success(200, 'success', $query->get());
        }

        return ResponseBuilder::paginated($query->paginate($request->query('per_page', 25)));
    }

    /**
     * GET /api/admins/app-modules/{id}
     * Show a single App Module with permissions and SSO Client credentials.
     */
    public function show($id)
    {
        $appModule = AppModule::with(['permission', 'ssoClient'])->find($id);

        if (!$appModule) {
            return response()->json([
                'status'  => 404,
                'message' => 'App Module not found.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $appModule);
    }

    /**
     * POST /api/admins/app-modules
     * Create a new App Module and automatically generate its SSO Client credentials.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:app_module,name',
            'url'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $appModule = AppModule::create([
            'name' => $request->name,
            'url'  => $request->url,
        ]);

        // Automatically generate SSO Client credentials linked to this AppModule
        $credentials = SsoClient::generateCredentials();
        $ssoClient = SsoClient::create([
            'app_module_id'      => $appModule->id,
            'name'               => $appModule->name . ' Client',
            'client_id'          => $credentials['client_id'],
            'client_secret'      => $credentials['client_secret'],
            'allowed_module_ids' => [$appModule->id],
            'callback_url'       => $appModule->url,
            'is_active'          => true,
        ]);

        // Load the relationship and append the plaintext secret for copy-on-create
        $appModuleData = $appModule->load('ssoClient')->toArray();
        $appModuleData['sso_client']['plain_secret'] = $credentials['plain_secret'];

        $this->logActivity(
            ActivityLogService::TYPE_APP_MODULE_CREATE,
            "Membuat modul aplikasi: {$appModule->name}",
            ['app_module_id' => $appModule->id, 'name' => $appModule->name, 'url' => $appModule->url]
        );

        return ResponseBuilder::success(201, 'App Module and SSO Client credentials generated successfully.', $appModuleData);
    }

    /**
     * PUT /api/admins/app-modules/{id}
     * Update an existing App Module and its linked SSO Client.
     */
    public function update(Request $request, $id)
    {
        $appModule = AppModule::with('ssoClient')->find($id);

        if (!$appModule) {
            return response()->json([
                'status'  => 404,
                'message' => 'App Module not found.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100|unique:app_module,name,' . $id,
            'url'  => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $appModule->update($request->only('name', 'url'));

        // Keep SsoClient name & callback URL in sync if linked
        if ($appModule->ssoClient) {
            $appModule->ssoClient->update([
                'name'         => $appModule->name . ' Client',
                'callback_url' => $appModule->url,
            ]);
        }

        $this->logActivity(
            ActivityLogService::TYPE_APP_MODULE_UPDATE,
            "Memperbarui modul aplikasi: {$appModule->name}",
            ['app_module_id' => $appModule->id, 'name' => $appModule->name, 'url' => $appModule->url]
        );

        return ResponseBuilder::success(200, 'App Module updated successfully.', $appModule->load('ssoClient'));
    }

    /**
     * DELETE /api/admins/app-modules/{id}
     * Soft-delete an App Module (database cascade delete removes the linked SSO Client).
     */
    public function destroy($id)
    {
        $appModule = AppModule::find($id);

        if (!$appModule) {
            return response()->json([
                'status'  => 404,
                'message' => 'App Module not found.',
                'data'    => [],
            ], 404);
        }

        // Soft-delete App Module
        $deletedName = $appModule->name;

        $appModule->delete();

        $this->logActivity(
            ActivityLogService::TYPE_APP_MODULE_DELETE,
            "Menghapus modul aplikasi: {$deletedName}",
            ['app_module_id' => $id, 'name' => $deletedName]
        );

        return response()->json([
            'status'  => 200,
            'message' => 'App Module and its associated SSO Client deleted successfully.',
            'data'    => [],
        ], 200);
    }

    /**
     * POST /api/admins/app-modules/{id}/reset-secret
     * Reset the client secret for the linked SSO Client.
     */
    public function resetSecret($id)
    {
        $appModule = AppModule::with('ssoClient')->find($id);

        if (!$appModule || !$appModule->ssoClient) {
            return response()->json([
                'status'  => 404,
                'message' => 'App Module or associated SSO Client not found.',
                'data'    => [],
            ], 404);
        }

        $credentials = SsoClient::generateCredentials();
        $appModule->ssoClient->update([
            'client_secret' => $credentials['client_secret']
        ]);

        $appModuleData = $appModule->toArray();
        $appModuleData['sso_client']['plain_secret'] = $credentials['plain_secret'];

        $this->logActivity(
            ActivityLogService::TYPE_SSO_SECRET_RESET,
            "Mereset SSO client secret modul: {$appModule->name}",
            ['app_module_id' => $appModule->id, 'name' => $appModule->name]
        );

        return ResponseBuilder::success(200, 'SSO Client secret reset successfully. Please copy the new secret now.', $appModuleData);
    }
}
