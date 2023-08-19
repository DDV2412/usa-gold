<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class Government extends Controller
{
    public function index(Request $request, $customer_id)
    {
        $tokenApi = env('WEBFLOW_API');
        $input = $request->all();

        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            if (isset($customer["items"][0]["government-identification"]) && is_object($customer["items"][0]["government-identification"])) {
                $governmentId = $customer["items"][0]["government-identification"]["_id"];

                $government = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/collections/".env('GOVERNMENT')."/items/".$governmentId);

                if($government->successful()){
                    $governmentField = [
                        "name"=> $customer["items"][0]["government-identification"]["name"],
                        "slug" => $customer["items"][0]["government-identification"]["slug"],
                        "identification-type"=> $input["identification_type"],
                        "state"=> $input["state"],
                        "_archived" => false,
                        "_draft" => false,
                    ];

                    $governmentUpdate = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->put("https://api.webflow.com/collections/".env('GOVERNMENT')."/items/".$governmentId, ['fields' => $governmentField]);

                    if($governmentUpdate->successful()){
                        $customerField = [
                            "name" => $customer["items"][0]["name"],
                            "slug" => $customer["items"][0]["slug"],
                            "government-identification" => $governmentUpdate['_id'],
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
                    }else{
                        return response()->json([
                            'success' => false,
                            'message' => $governmentUpdate->json()
                        ], 404);
                    }
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Government ID not found'
                    ], 404);
                }
            } else {
                $governmentField = [
                    "name"=> $input["identification_number"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    "identification-type"=> $input["identification_type"],
                    "state"=> $input["state"],
                    "_archived" => false,
                    "_draft" => false,
                ];

                $governmentCreate = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/collections/".env('GOVERNMENT')."/items/", ['fields' => $governmentField]);

                if($governmentCreate->successful()){
                    $customerField = [
                        "name" => $customer["items"][0]["name"],
                        "slug" => $customer["items"][0]["slug"],
                        "government-identification" => $governmentCreate['_id'],
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
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => $governmentCreate->json()
                    ], 404);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
}
