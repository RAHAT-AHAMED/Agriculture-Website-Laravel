<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    
    public function show(){
        $amenities = Amenity::get();
        return view('admin.amenity_view',compact('amenities'));
    }
    
    public function create(){
        return view('admin.amenity_create');
    }

    public function store(Request $request){
        $amenity = new Amenity();

        $request->validate([
            'name' => 'required'
        ],[
            'name.required' => 'Amenity name is required',
        ]);
        
        $amenity->name = $request->name;
        
        $amenity->save();
        return redirect()->route('admin_amenity_show')->with('success_message','Amenity Created Successfully');
    }
    
    public function edit($id){
        $amenity = Amenity::where('id',$id)->first();
        return view('admin.amenity_edit',compact('amenity'));
    }

    public function update(Request $request,$id){
        $amenity = Amenity::where('id',$id)->first();
   
        $request->validate([
            'name' => 'required'
        ],[
            'name.required' => 'SubCategory name is required'
        ]);
        
        $amenity->name = $request->name;
        
        $amenity->update();
        return redirect()->route('admin_amenity_show')->with('success_message','Amenity Updated Successfully');
    }

    public function delete($id){
        $amenity = Amenity::where('id',$id)->first();
        $amenity->delete();
        return redirect()->route('admin_amenity_show')->with('success_message','Amenity Deleted Successfully');
    }
}
