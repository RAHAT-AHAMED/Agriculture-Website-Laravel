<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvolvedController extends Controller
{
    public function donate(){
        return view('front.donate');
    }

    public function sponsor(){
        return view('front.sponsor');
    }

    public function sponsor_details(){
        return view('front.sponsor_details');
    }
}
