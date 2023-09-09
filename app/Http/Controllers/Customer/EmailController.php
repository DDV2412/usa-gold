<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class EmailController extends Controller
{
    public function index(Request $request)
    {

        $pack_id = $request->query('id');
        $tokenApi = env('WEBFLOW_API');
    
        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('GOLDPACK')."/items/".$pack_id);
    
        if ($customer->successful()) {

            $frontendUrl = env('FRONTEND_URL') . '/request-gold-pack/' . $customer["fieldData"]["slug"];

            $data = [
                "name" => $customer["fieldData"]["name"] . ' ' . $customer["fieldData"]["last-name"],
                "frontendUrl" => $frontendUrl
            ];

            Mail::send('template.email', $data, function ($message) use ($customer) {
                $message->to($customer["fieldData"]["email"], $customer["fieldData"]["name"] . ' ' . $customer["fieldData"]["last-name"])
                        ->subject('Your New Request a Gold Pack')
                        ->from('usagold.us@gmail.com', 'USA Gold');
            });

            return response()->json(['message' => 'Email sent successfully']);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Please check your input address or email'
            ], 400);
        }

    }
}

