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
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            if (isset($customer["fieldData"]["government-identification"])) {
                $governmentId = $customer["fieldData"]["government-identification"];

                $government = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('GOVERNMENT')."/items/".$governmentId);

                if($government->successful()){
                    $governmentField = [
                        "name"=> $input["identification_number"],
                        "slug" => $government["fieldData"]["slug"],
                        "identification-type"=> $input["identification_type"],
                        "state"=> $input["state"]
                    ];

                    $governmentUpdate = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('GOVERNMENT')."/items/".$governmentId, ['fieldData' => $governmentField, "isArchived" => false,
                    "isDraft" => false]);

                    if($governmentUpdate->successful()){
                        $customerField = [
                            "name" => $customer["fieldData"]["name"],
                            "slug" => $customer["fieldData"]["slug"],
                            "government-identification" => $governmentUpdate['id'],
                        ];
        
                        $responseCustomer = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $tokenApi,
                        ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fieldData' => $customerField, "isArchived" => false,
                        "isDraft" => false]);
        
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
                ];

                $governmentCreate = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('GOVERNMENT')."/items/", ['fieldData' => $governmentField, "isArchived" => false,
                "isDraft" => false]);

                if($governmentCreate->successful()){
                    $customerField = [
                        "name" => $customer["fieldData"]["name"],
                        "slug" => $customer["fieldData"]["slug"],
                        "government-identification" => $governmentCreate['id'],
                    ];
    
                    $responseCustomer = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fieldData' => $customerField, "isArchived" => false,
                    "isDraft" => false]);
    
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
