<?php

namespace App\Http\Controllers\Front;

use App\Models\Post;
use App\Models\Room;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(){
        $rooms = Room::get();
        $testimonials = Testimonial::get();
        return view('front.home',compact('rooms','testimonials'));
    }
    
}
