<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;  
use App\Models\TxUserModulPermission; 


use App\Http\Helper\ResponseBuilder;
use Illuminate\Support\Facades\DB; 

class TxUserModulPermissionController extends Controller
{  
    public function index(Request $request)
    {
        // $userId = $request->input('user_id');
        // if (!$userId) {
        //     return ResponseBuilder::error(400, "user_id is required");
        // }

        $data = TxUserModulPermission::with('appModul', 'appModul.permission')->get();

        return ResponseBuilder::success(200, "success", $data);
    }
     
}
