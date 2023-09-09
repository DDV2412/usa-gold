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
            if (isset($customer["fieldData"]["referrals"])) {
                $referralId = $customer["fieldData"]["referrals"];

                $referral = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('REFERRALS')."/items/".$referralId);

                if($referral->successful()){
                    $referralsField = [
                        "name"=> $input["first_name"],
                        "slug" =>  $referral["fieldData"]["slug"],
                        "last-name"=> $input["last_name"],
                        "gender" => $input["gender"] ?? "",
                        "email"=> $input["email"],
                        "phone-number"=> $input["phone_number"],
                        "address"=> $input["address"],
                        "unit-app"=> $input["unit_app"] ?? "",
                        "city"=> $input["city"],
                        "state"=> $input["state"],
                        "zip"=> $input["zip"]
                    ];

                    $referralUpdate = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('REFERRALS')."/items/".$referralId, ['fieldData' => $referralsField, "isArchived" => false, "isDraft" => false]);

                    if($referralUpdate->successful()){
                        $customerField = [
                            "name" => $customer["fieldData"]["name"],
                            "slug" => $customer["fieldData"]["slug"],
                            "referrals" => $referralUpdate['id']
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
                    "zip"=> $input["zip"]
                ];

                $referralCreate = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('REFERRALS')."/items/", ['fieldData' => $referralsField, "isArchived" => false, "isDraft" => false]);

                if($referralCreate->successful()){
                    $customerField = [
                        "name" => $customer["fieldData"]["name"],
                        "slug" => $customer["fieldData"]["slug"],
                        "referrals" => $referralCreate['id']
                    ];
    
                    $responseCustomer = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fieldData' => $customerField]);
    
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
