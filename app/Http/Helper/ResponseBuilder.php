<?php

namespace  App\Http\Helper;

class ResponseBuilder
{
    public static function success($status = 200, $message = "", $data = [])
    {
        if ($data) {
            # code...
            return response()->json([
                "status" => $status,
                "message" => $message,
                // "total_data" => count((array)$data),
                "data" => $data
            ], $status);
        } else {
            return response()->json([
                "status" => $status,
                "message" => $message,
                "total_data" => 0,
                "data" => []
            ], $status);
        }
    }
    /**
     * Respons untuk hasil paginate(). Bentuknya sengaja disamakan dengan
     * UnitController agar seluruh endpoint list memakai kontrak yang sama:
     * data = array item, meta = informasi halaman.
     */
    public static function paginated($paginator, $message = "success")
    {
        return response()->json([
            "status"  => 200,
            "message" => $message,
            "data"    => $paginator->items(),
            "meta"    => [
                "current_page" => $paginator->currentPage(),
                "per_page"     => $paginator->perPage(),
                "total"        => $paginator->total(),
                "last_page"    => $paginator->lastPage(),
                "from"         => $paginator->firstItem(),
                "to"           => $paginator->lastItem(),
            ],
        ], 200);
    }

    public static function error($status = "", $error = "", $data = [])
    {
        return [
            "status" => $status,
            "message" => $error,
            "data" => $data
        ];
    }
}
