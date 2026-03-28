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
    public static function error($status = "", $error = "", $data = [])
    {
        return [
            "status" => $status,
            "message" => $error,
            "data" => $data
        ];
    }
}
