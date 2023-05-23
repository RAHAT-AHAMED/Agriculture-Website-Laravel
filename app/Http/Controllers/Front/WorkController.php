<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function wash(){
        return view('front.wash');
    }

    public function scale(){
        return view('front.scale');   
    }
    
    public function grow(){
        return view('front.grow'); 
    }
    
    public function health(){
        return view('front.health');
    }
}
