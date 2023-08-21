<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class Profile extends Controller
{
    public function index(Request $request, $customer_id)
    {
        $tokenApi = env('WEBFLOW_API');
        $input = $request->all();

        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            $customerField = [
                "name"=> $input["first_name"],
                "slug" => $customer["items"][0]['slug'],
                "last-name"=> $input["last_name"],
                "gender" => $input["gender"] ?? "",
                "email"=> $customer["items"][0]["email"],
                "phone-number"=> $input["phone_number"],
                "address"=> $input["address"],
                "unit-app"=> $input["unit_app"] ?? "",
                "city"=> $input["city"],
                "state"=> $input["state"],
                "zip"=> $input["zip"],
                "_archived" => false,
                "_draft" => false,
            ];
            

            $responseCustomer = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenApi,
            ])->timeout(30)->put("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id, ['fields' => $customerField]);

            if ($responseCustomer->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $responseCustomer->json()
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Please check your input address or email'
                ], 400);
            }

        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
}
