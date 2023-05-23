<?php

namespace App\Http\Controllers\Front;

// use Mail;
use Stripe;
use Stripe\Charge;
use App\Models\Room;
use App\Models\Order;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Payment;
use App\Mail\Websitemail;
use App\Models\BookedRoom;
use App\Models\OrderDetail;
use PayPal\Api\Transaction;
use Illuminate\Http\Request;
use PayPal\Api\PaymentExecution;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;  


class BookingController extends Controller
{


    public function cart_submit(Request $request)
    {
        $request->validate([
            'room_id' => 'required',
            'qty' => 'required'
        ]);
        
        // Put The Requested Data Into Session
        session()->push('cart_room_id',$request->room_id);
        session()->push('cart_qty',$request->qty);
        
        return redirect()->back()->with('success', 'Product is added to the cart successfully.');



    }



    public function cart_view()
    {
        return view('front.cart');
    }




    // Add To Cart Item Delete
    public function cart_delete($id)
    {
        $arr_cart_room_id = array();
        $i=0;
        foreach(session()->get('cart_room_id') as $value) {
            $arr_cart_room_id[$i] = $value;
            $i++;
        }

        $arr_cart_qty = array();
        $i=0;
        foreach(session()->get('cart_qty') as $value) {
            $arr_cart_qty[$i] = $value;
            $i++;
        }

        session()->forget('cart_room_id');
        session()->forget('cart_qty');

        for($i=0;$i<count($arr_cart_room_id);$i++)
        {
            if($arr_cart_room_id[$i] == $id) 
            {
                continue;    
            }
            else
            {
                session()->push('cart_room_id',$arr_cart_room_id[$i]);
                session()->push('cart_qty',$arr_cart_qty[$i]);
            }
        }

        return redirect()->back()->with('success', 'Cart item is deleted.');

    }

    
    public function lets_donate(){
        return view('front.donate_system');
    }


    // Checkout
    public function checkout()
    {
        if(!Auth::guard('customer')->check()) {
            return redirect()->back()->with('error', 'You must have to login in order to checkout');
        }

        if(!session()->has('cart_room_id')) {
            return redirect()->back()->with('error', 'There is no item in the cart');
        }

        return view('front.checkout');
    }


    
    // Payment Page Show
    public function payment(Request $request)
    {
        if(!Auth::guard('customer')->check()) {
            return redirect()->back()->with('error', 'You must have to login in order to checkout');
        }

        if(!session()->has('cart_room_id')) {
            return redirect()->back()->with('error', 'There is no item in the cart');
        }

        $request->validate([
            'billing_name' => 'required',
            'billing_email' => 'required|email',
            'billing_phone' => 'required',
            'billing_country' => 'required',
            'billing_address' => 'required',
            'billing_state' => 'required',
            'billing_city' => 'required',
            'billing_zip' => 'required'
        ]);

        session()->put('billing_name',$request->billing_name);
        session()->put('billing_email',$request->billing_email);
        session()->put('billing_phone',$request->billing_phone);
        session()->put('billing_country',$request->billing_country);
        session()->put('billing_address',$request->billing_address);
        session()->put('billing_state',$request->billing_state);
        session()->put('billing_city',$request->billing_city);
        session()->put('billing_zip',$request->billing_zip);

        return view('front.payment');
    }






    // Payment using Paypal
    public function paypal($final_price)
    {
        $client = 'AVxAVMrGZpPkOfwWKX0uR4e8aXiM8aKNA_Z3C-Q6xyYLeFwxnvn2S2XlLni2ActwHcxS5tEHEq9ax09Z';
        $secret = 'ENFrPoyFqY32KGoRzOYRJRc9pVjlP6p6xvQNBnQY24MZ8US_G97pYf3RR3mZCOuD4A3t6_AaYDn1AZ8C';

        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                $client, // ClientID
                $secret // ClientSecret
            )
        );

        $paymentId = request('paymentId');
        $payment = Payment::get($paymentId, $apiContext);

        $execution = new PaymentExecution();
        $execution->setPayerId(request('PayerID'));

        $transaction = new Transaction();
        $amount = new Amount();
        $details = new Details();

        $details->setShipping(0)
            ->setTax(0)
            ->setSubtotal($final_price);

        $amount->setCurrency('USD');
        $amount->setTotal($final_price);
        $amount->setDetails($details);
        $transaction->setAmount($amount);
        $execution->addTransaction($transaction);
        $result = $payment->execute($execution, $apiContext);

        if($result->state == 'approved')
        {
            $paid_amount = $result->transactions[0]->amount->total;
            
            $order_no = time();

            $statement = DB::select("SHOW TABLE STATUS LIKE 'orders'");
            $ai_id = $statement[0]->Auto_increment;

            $obj = new Order();
            $obj->customer_id = Auth::guard('customer')->user()->id;
            $obj->order_no = $order_no;
            $obj->transaction_id = $result->id;
            $obj->payment_method = 'PayPal';
            $obj->paid_amount = $paid_amount;
            $obj->status = 'Completed';
            $obj->save();
            
            $arr_cart_room_id = array();
            $i=0;
            foreach(session()->get('cart_room_id') as $value) {
                $arr_cart_room_id[$i] = $value;
                $i++;
            }

            $arr_cart_qty = array();
            $i=0;
            foreach(session()->get('cart_qty') as $value) {
                $arr_cart_qty[$i] = $value;
                $i++;
            }

            

            for($i=0;$i<count($arr_cart_room_id);$i++)
            {
                $r_info = Room::where('id',$arr_cart_room_id[$i])->first();
                $sub = $r_info->price*$arr_cart_qty[$i];

                $obj = new OrderDetail();
                $obj->order_id = $ai_id;
                $obj->room_id = $arr_cart_room_id[$i];
                $obj->order_no = $order_no;
                $obj->subtotal = $sub;
                $obj->save();

                

            }

            $subject = 'New Order';
            $message = 'You have made an order for hotel booking. The booking information is given below: <br>';
            $message .= '<br>Order No: '.$order_no;
            $message .= '<br>Transaction Id: '.$result->id;
            $message .= '<br>Payment Method: PayPal';
            $message .= '<br>Paid Amount: '.$paid_amount;

            for($i=0;$i<count($arr_cart_room_id);$i++) {

                $r_info = Room::where('id',$arr_cart_room_id[$i])->first();

                $message .= '<br>Room Name: '.$r_info->name;
                $message .= '<br>Price Per Night: $'.$r_info->price;
            }            

            $customer_email = Auth::guard('customer')->user()->email;

            Mail::to($customer_email)->send(new Websitemail($subject,$message));

            session()->forget('cart_room_id');
            session()->forget('cart_qty');
            session()->forget('billing_name');
            session()->forget('billing_email');
            session()->forget('billing_phone');
            session()->forget('billing_country');
            session()->forget('billing_address');
            session()->forget('billing_state');
            session()->forget('billing_city');
            session()->forget('billing_zip');

            return redirect()->route('home')->with('success', 'Payment is successful');
        }
        else
        {
            return redirect()->route('home')->with('error', 'Payment is failed');
        }


    }





    // Payment using stripe
    public function stripe(Request $request,$final_price)
    {
        $stripe_secret_key = 'sk_test_51JeC9fBYPKDnQGbGG6Vdr1OsHt6ALUEebObsL3f4Euwxme1XJ7YhZJZ65LfUHcflYxaPTpNkvH9VjB9LV8ZZtyzV009A9oUwVu';
        $cents = $final_price*100;
        Stripe\Stripe::setApiKey($stripe_secret_key);
        $response = Stripe\Charge::create ([
            "amount" => $cents,
            "currency" => "usd",
            "source" => $request->stripeToken,
            "description" => env('APP_NAME')
        ]);

        $responseJson = $response->jsonSerialize();
        $transaction_id = $responseJson['balance_transaction'];
        $last_4 = $responseJson['payment_method_details']['card']['last4'];

        $order_no = time();

        $statement = DB::select("SHOW TABLE STATUS LIKE 'orders'");
        $ai_id = $statement[0]->Auto_increment;

        $obj = new Order();
        $obj->customer_id = Auth::guard('customer')->user()->id;
        $obj->order_no = $order_no;
        $obj->transaction_id = $transaction_id;
        $obj->payment_method = 'Stripe';
        $obj->card_last_digit = $last_4;
        $obj->paid_amount = $final_price;
        $obj->status = 'Completed';
        $obj->save();
        
        $arr_cart_room_id = array();
        $i=0;
        foreach(session()->get('cart_room_id') as $value) {
            $arr_cart_room_id[$i] = $value;
            $i++;
        }

        $arr_cart_qty = array();
        $i=0;
        foreach(session()->get('cart_qty') as $value) {
            $arr_cart_qty[$i] = $value;
            $i++;
        }

        
        for($i=0;$i<count($arr_cart_room_id);$i++)
        {
            $r_info = Room::where('id',$arr_cart_room_id[$i])->first();
            $sub = $r_info->price*$arr_cart_qty[$i];

            $obj = new OrderDetail();
            $obj->order_id = $ai_id;
            $obj->room_id = $arr_cart_room_id[$i];
            $obj->order_no = $order_no;
            $obj->subtotal = $sub;
            $obj->save();

        }

        $subject = 'New Order';
        $message = 'You have made an order for an Instrument. The information is given below: <br>';
        $message .= '<br>Order No: '.$order_no;
        $message .= '<br>Transaction Id: '.$transaction_id;
        $message .= '<br>Payment Method: Stripe';
        $message .= '<br>Paid Amount: '.$final_price;

        for($i=0;$i<count($arr_cart_room_id);$i++) {

            $r_info = Room::where('id',$arr_cart_room_id[$i])->first();

            $message .= '<br>Name: '.$r_info->name;
            $message .= '<br>Price: $'.$r_info->price;
        }            

        $customer_email = Auth::guard('customer')->user()->email;

        Mail::to($customer_email)->send(new Websitemail($subject,$message));

        // Delete Session Data
        session()->forget('cart_room_id');
        session()->forget('billing_name');
        session()->forget('billing_email');
        session()->forget('billing_phone');
        session()->forget('billing_country');
        session()->forget('billing_address');
        session()->forget('billing_state');
        session()->forget('billing_city');
        session()->forget('billing_zip');

        return redirect()->route('home')->with('success', 'Payment is successful');


    }


    



}