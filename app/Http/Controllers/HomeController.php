<?php

namespace App\Http\Controllers;

use App\Models\LinkItems;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home() {
        $items = LinkItems::all();
        return view('front-page.home', ['items' => $items]);
    }
    

    public function about(){
        return view('front-page.about');
    }

    public function blogs(){
        return view('front-page.blogs');
    }
}

