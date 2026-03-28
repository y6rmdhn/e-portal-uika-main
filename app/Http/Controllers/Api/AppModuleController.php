<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request; 
use App\Models\AppModule;  


use App\Http\Helper\ResponseBuilder;
use Illuminate\Support\Facades\DB; 

class AppModuleController extends Controller
{  
    public function index(Request $request)
    {  
        $data = AppModule::with('permission')->get();

        return ResponseBuilder::success(200, "success", $data);
    }
     
}
