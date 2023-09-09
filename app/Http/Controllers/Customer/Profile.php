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
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            $customerField = [
                "name"=> $input["first_name"],
                "slug" => $customer["fieldData"]['slug'],
                "last-name"=> $input["last_name"],
                "gender" => $input["gender"] ?? "",
                "email"=> $customer["fieldData"]["email"],
                "phone-number"=> $input["phone_number"],
                "address"=> $input["address"],
                "unit-app"=> $input["unit_app"] ?? "",
                "city"=> $input["city"],
                "state"=> $input["state"],
                "zip"=> $input["zip"]
            ];
            

            $responseCustomer = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenApi,
            ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fieldData' => $customerField, "isArchived" => false, "isDraft" => false]);

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
