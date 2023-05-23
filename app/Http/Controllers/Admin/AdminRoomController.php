<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Room;
use App\Models\RoomPhoto;

class AdminRoomController extends Controller
{
    
    // Show All Instruments
    public function show(){
        $rooms = Room::get();
        return view('admin.room_view',compact('rooms'));
    }

    // Create Instrument Page Show
    public function create(){
        $amenities = Amenity::get();
        return view('admin.room_create',compact('amenities'));
    }

    
    // Instrument Data Store
    public function store(Request $request){
        

        $request->validate([
            'featured_photo' => 'required',
            'name' => 'required',
            'description' => 'required',
            'price' => 'required'
        ],[
            'featured_photo.required' => 'Photo is required',
            'name.required' => 'Name is required',
            'description.required' => 'Description is required',
            'price.required' => 'Price is required'
        ]);




        /*check IF amenities exist or not*/
        $amenities = "";
        $i=0;
        if (isset($request->arr_amenities)) 
        {
            foreach($request->arr_amenities as $item){
                if ($i == 0) 
                {
                    $amenities .= $item;
                }
                else 
                {
                    $amenities .= ','.$item;
                }
                $i++;
            }
        }

      
        // Featured Photo Upload
        $ext = $request->file('featured_photo')->extension();
        $final_name = 'featured_photo_'.time().'.'.$ext;
        $request->file('featured_photo')->move(public_path('uploads/'),$final_name);

        $room = new Room();
        $room->name = $request->name;
        $room->description = $request->description;
        $room->price = $request->price;
        
        $room->amenities = $amenities;
        $room->featured_photo = $final_name;
        $room->video_id = $request->video_id;
        $room->save();
        return redirect()->route('admin_room_show')->with('success_message','Instrument Created Successfully');
    }


    // Instrument Data Edit
    public function edit($id){
        $amenities = Amenity::get();
        $room = Room::where('id',$id)->first();

        $existing_amenities = array();
        if ($room->amenities != "") 
        {
            $existing_amenities = explode(',',$room->amenities);
        }

        return view('admin.room_edit',compact('amenities','room','existing_amenities'));
    }


    // Instrument Data Update
    public function update(Request $request,$id){

        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'required'
        ],[
            'name.required' => 'Name is required',
            'description.required' => 'Description is required',
            'price.required' => 'Price is required'
        ]);

        $room = Room::where('id',$id)->first();

        // featured photo upload
        if ($request->hasFile('featured_photo')) {
            $request->validate([
                'featured_photo' => 'image|mimes:png,jpg,jpeg,gif'
            ]);
            unlink(public_path('uploads/'.$room->featured_photo));
            $ext = $request->file('featured_photo')->extension();
            $final_name = 'featured_photo'.time().'.'.$ext;
            $request->file('featured_photo')->move(public_path('uploads/'),$final_name);
            $room->featured_photo = $final_name;
        }

        

        /*check IF amenities exist or not*/
        $amenities = "";
        $i=0;
        if (isset($request->arr_amenities)) 
        {
            foreach($request->arr_amenities as $item){
                if ($i == 0) 
                {
                    $amenities .= $item;
                }
                else 
                {
                    $amenities .= ','.$item;
                }
                $i++;
            }
        }

        $room->name = $request->name;
        $room->description = $request->description;
        $room->price = $request->price;
        $room->amenities = $amenities;
        
        $room->video_id = $request->video_id;
        $room->update();
        return redirect()->route('admin_room_show')->with('success_message','Information Updated Successfully');
    
    }


    // Instrument Data delete
    public function delete($id){
        $room = Room::where('id',$id)->first();
        unlink(public_path('uploads/'.$room->featured_photo));
        $room->delete();

        $room_photos = RoomPhoto::where('room_id',$id)->get();
        foreach($room_photos as $room_photo){
            unlink(public_path('uploads/'.$room_photo->photo));
            $room_photo->delete();
        }


        return redirect()->route('admin_room_show')->with('success_message','Data is Deleted Successfully');
    }


    // Instrument Gallery
    public function gallery($id){
        $room = Room::where('id',$id)->first();
        $room_photos = RoomPhoto::where('room_id',$id)->get();
        return view('admin.room_gallery',compact('room','room_photos'));
    }
    

    // Instrument Gallery Store
    public function gallery_store(Request $request,$id){
        
        $request->validate([
            'photo' => 'required'
        ],[
            'photo.required' => 'Photo is required'
        ]);

        // Photo Upload
        $ext = $request->file('photo')->extension();
        $final_name = 'photo_'.time().'.'.$ext;
        $request->file('photo')->move(public_path('uploads/'),$final_name);


        $room_photo = new RoomPhoto();
        $room_photo->photo = $final_name;
        $room_photo->room_id = $id;

        $room_photo->save();

        return redirect()->back()->with('success_message','Photo is added Successfully');


    }


    // Instrument Gallery delete
    public function gallery_delete($id){
        $room = RoomPhoto::where('id',$id)->first();
        // unlink the photo
        unlink(public_path('uploads/'.$room->photo));
        $room->delete();
        return redirect()->back()->with('success_message','Photo is Deleted Successfully');
    }



}
