<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SsoIntegrationTemplate;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SsoIntegrationController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $templates = SsoIntegrationTemplate::orderBy('category')
            ->orderBy('order')
            ->get();

        return $this->successResponse($templates, 'Templates retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'category'     => 'required|in:frontend,backend',
            'language'     => 'required|string|max:50',
            'icon'         => 'nullable|string|max:100',
            'code_snippet' => 'required|string',
            'description'  => 'nullable|string',
            'dependencies' => 'nullable|string',
            'order'        => 'nullable|integer',
            'is_active'    => 'nullable|boolean',
        ]);

        $template = SsoIntegrationTemplate::create($request->all());

        return $this->successResponse($template, 'Template created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $template = SsoIntegrationTemplate::findOrFail($id);
        return $this->successResponse($template, 'Template retrieved successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = SsoIntegrationTemplate::findOrFail($id);

        $request->validate([
            'name'         => 'sometimes|required|string|max:100',
            'category'     => 'sometimes|required|in:frontend,backend',
            'language'     => 'sometimes|required|string|max:50',
            'icon'         => 'nullable|string|max:100',
            'code_snippet' => 'sometimes|required|string',
            'description'  => 'nullable|string',
            'dependencies'  => 'nullable|string',
            'order'        => 'nullable|integer',
            'is_active'    => 'nullable|boolean',
        ]);

        $template->update($request->all());

        return $this->successResponse($template, 'Template updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $template = SsoIntegrationTemplate::findOrFail($id);
        $template->delete();

        return $this->successResponse(null, 'Template deleted successfully');
    }
}
