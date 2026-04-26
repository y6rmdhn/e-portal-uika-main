<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Berhasil.',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = [
            'status'  => true,
            'message' => $message,
            'data'    => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse(
        string $message = 'Terjadi kesalahan.',
        int $statusCode = 500,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'status'  => false,
            'message' => $message,
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    protected function paginatedResponse(
        mixed $paginator,
        string $message = 'Berhasil.',
        string $resourceClass = null
    ): JsonResponse {
        $items = $resourceClass
            ? $resourceClass::collection($paginator)
            : $paginator->items();

        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }
}
