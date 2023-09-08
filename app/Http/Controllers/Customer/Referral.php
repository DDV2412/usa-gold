<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class Referral extends Controller
{
    public function index(Request $request, $customer_id)
    {
        $tokenApi = env('WEBFLOW_API');
        $input = $request->all();

        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            if (isset($customer["items"][0]["referrals"])) {
                $referralId = $customer["items"][0]["referrals"];

                $referral = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('REFERRALS')."/items/".$referralId);

                if($referral->successful()){
                    $referralsField = [
                        "name"=> $input["first_name"],
                        "slug" =>  $referral["items"][0]["slug"],
                        "last-name"=> $input["last_name"],
                        "gender" => $input["gender"] ?? "",
                        "email"=> $input["email"],
                        "phone-number"=> $input["phone_number"],
                        "address"=> $input["address"],
                        "unit-app"=> $input["unit_app"] ?? "",
                        "city"=> $input["city"],
                        "state"=> $input["state"],
                        "zip"=> $input["zip"],
                        "_archived" => false,
                        "_draft" => false,
                    ];

                    $referralUpdate = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->put("https://api.webflow.com/beta/collections/".env('REFERRALS')."/items/".$referralId, ['fields' => $referralsField]);

                    if($referralUpdate->successful()){
                        $customerField = [
                            "name" => $customer["items"][0]["name"],
                            "slug" => $customer["items"][0]["slug"],
                            "referrals" => $referralUpdate['_id'],
                            "_archived" => false,
                            "_draft" => false,
                        ];
        
                        $responseCustomer = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $tokenApi,
                        ])->timeout(30)->put("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fields' => $customerField]);
        
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
                            'message' => $referralUpdate->json()
                        ], 404);
                    }
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Referral ID not found'
                    ], 404);
                }
            } else {
                $referralsField = [
                    "name"=> $input["first_name"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    "last-name"=> $input["last_name"],
                    "gender" => $input["gender"] ?? "",
                    "email"=> $input["email"],
                    "phone-number"=> $input["phone_number"],
                    "address"=> $input["address"],
                    "unit-app"=> $input["unit_app"] ?? "",
                    "city"=> $input["city"],
                    "state"=> $input["state"],
                    "zip"=> $input["zip"],
                    "_archived" => false,
                    "_draft" => false,
                ];

                $referralCreate = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('REFERRALS')."/items/", ['fields' => $referralsField]);

                if($referralCreate->successful()){
                    $customerField = [
                        "name" => $customer["items"][0]["name"],
                        "slug" => $customer["items"][0]["slug"],
                        "referrals" => $referralCreate['_id'],
                        "_archived" => false,
                        "_draft" => false,
                    ];
    
                    $responseCustomer = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->put("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fields' => $customerField]);
    
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
                        'message' => $referralCreate->json()
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
