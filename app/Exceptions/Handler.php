<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
            return response()->json([
                'status'  => 403,
                'message' => 'Anda tidak memiliki akses ke resource ini.',
                'data'    => []
            ], 403);
        }

        // Aplikasi ini API JSON — jangan pernah balas HTML ke klien.
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Endpoint tidak ditemukan.',
                    'data'    => []
                ], 404);
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return response()->json([
                    'status'  => 405,
                    'message' => 'Method tidak diizinkan untuk endpoint ini.',
                    'data'    => []
                ], 405);
            }
        }

        return parent::render($request, $e);
    }
}
