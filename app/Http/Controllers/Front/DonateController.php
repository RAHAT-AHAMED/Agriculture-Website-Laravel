<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Donate;
use Illuminate\Http\Request;

class DonateController extends Controller
{
    public function donate_submit(Request $request){

        $request->validate([
            'card_number' => 'required',
            'card_holder' => 'required',
            'expiration_mm' => 'required',
            'expiration_yy' => 'required',
            'cvv' => 'required',
            'amount' => 'required',
        ],[
            'card_number.required' => 'Card Number is required',
            'card_holder.required' => 'Card Holder Name is required',
            'expiration_mm.required' => 'Month is required',
            'expiration_yy.required' => 'Year is required',
            'cvv.required' => 'CVV is required',
            'amount.required' => 'Amount is required'
        ]);

        $donate = new Donate();
        $donate->card_number = $request->card_number;
        $donate->card_holder = $request->card_holder;
        $donate->expiration_mm = $request->expiration_mm;
        $donate->expiration_yy = $request->expiration_yy;
        $donate->cvv = $request->cvv;
        $donate->amount = $request->amount;
        $donate->save();
        return redirect()->route('home')->with('success','Donation Successfully');
    }
}
