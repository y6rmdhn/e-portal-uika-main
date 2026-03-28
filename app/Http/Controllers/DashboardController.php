<?php

namespace App\Http\Controllers;

use App\Models\LinkItems;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Helper\ResponseBuilder;

class DashboardController extends Controller
{
    public function index(){
        $items = LinkItems::all();
        return view('dashboard', ['items' => $items]);
        
    }

    public function getItems(){
        $items = LinkItems::all();
        return response()->json([
            "status" => 200,
            "message" => 'success',
            "data" => $items
        ], 200);
        return ResponseBuilder::success(200, "success", $items); 
        
    }
}
