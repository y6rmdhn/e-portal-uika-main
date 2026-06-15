<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SsoClient;
use App\Http\Helper\ResponseBuilder;

class SsoClientController extends Controller
{
    /**
     * GET /api/admins/sso-clients
     * List all SSO Clients.
     */
    public function index(Request $request)
    {
        $data = SsoClient::all();

        return ResponseBuilder::success(200, 'success', $data);
    }

    /**
     * GET /api/admins/sso-clients/{id}
     * Show single SSO Client details.
     */
    public function show($id)
    {
        $client = SsoClient::find($id);

        if (!$client) {
            return response()->json([
                'status'  => 404,
                'message' => 'SSO Client not found.',
                'data'    => [],
            ], 404);
        }

        return ResponseBuilder::success(200, 'success', $client);
    }

    /**
     * POST /api/admins/sso-clients
     * Create a new SSO Client.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:100|unique:sso_clients,name',
            'callback_url'       => 'required|string|max:255',
            'description'        => 'nullable|string',
            'allowed_module_ids' => 'nullable|array',
            'is_active'          => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        // Generate Client ID and Client Secret
        $credentials = SsoClient::generateCredentials();

        $client = SsoClient::create([
            'name'               => $request->name,
            'client_id'          => $credentials['client_id'],
            'client_secret'      => $credentials['client_secret'],
            'allowed_module_ids' => $request->allowed_module_ids,
            'callback_url'       => $request->callback_url,
            'description'        => $request->description,
            'is_active'          => $request->input('is_active', true),
        ]);

        // We temporarily append the plaintext secret to the model data
        // so the admin can copy it (it is hidden from JSON responses by default).
        $clientData = $client->toArray();
        $clientData['plain_secret'] = $credentials['plain_secret'];

        return ResponseBuilder::success(201, 'SSO Client created successfully. Please copy the client secret now as it will not be displayed again.', $clientData);
    }

    /**
     * PUT /api/admins/sso-clients/{id}
     * Update an existing SSO Client details.
     */
    public function update(Request $request, $id)
    {
        $client = SsoClient::find($id);

        if (!$client) {
            return response()->json([
                'status'  => 404,
                'message' => 'SSO Client not found.',
                'data'    => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'               => 'sometimes|required|string|max:100|unique:sso_clients,name,' . $id,
            'callback_url'       => 'sometimes|required|string|max:255',
            'description'        => 'nullable|string',
            'allowed_module_ids' => 'nullable|array',
            'is_active'          => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors()->first(),
                'data'    => [],
            ], 422);
        }

        $client->update($request->only('name', 'callback_url', 'description', 'allowed_module_ids', 'is_active'));

        return ResponseBuilder::success(200, 'SSO Client updated successfully.', $client);
    }

    /**
     * DELETE /api/admins/sso-clients/{id}
     * Delete an SSO Client registration.
     */
    public function destroy($id)
    {
        $client = SsoClient::find($id);

        if (!$client) {
            return response()->json([
                'status'  => 404,
                'message' => 'SSO Client not found.',
                'data'    => [],
            ], 404);
        }

        $client->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'SSO Client deleted successfully.',
            'data'    => [],
        ], 200);
    }

    /**
     * POST /api/admins/sso-clients/{id}/reset-secret
     * Reset the client secret key.
     */
    public function resetSecret($id)
    {
        $client = SsoClient::find($id);

        if (!$client) {
            return response()->json([
                'status'  => 404,
                'message' => 'SSO Client not found.',
                'data'    => [],
            ], 404);
        }

        $credentials = SsoClient::generateCredentials();
        $client->update([
            'client_secret' => $credentials['client_secret']
        ]);

        $clientData = $client->toArray();
        $clientData['plain_secret'] = $credentials['plain_secret'];

        return ResponseBuilder::success(200, 'SSO Client secret reset successfully. Please copy the new client secret now.', $clientData);
    }
}
