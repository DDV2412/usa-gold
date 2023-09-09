<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class Payment extends Controller
{
    public function index(Request $request, $customer_id)
    {
        $tokenApi = env('WEBFLOW_API');
        $input = $request->all();

        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            if (isset($customer["fieldData"]["payment-option"])) {
                $paymentId = $customer["fieldData"]["payment-option"];

                $payment = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('PAYMENT')."/items/".$paymentId);

                if($payment->successful()){
                    $paymentsField = [
                        "name"=> $payment["fieldData"]["name"],
                        "slug" =>  $payment["fieldData"]["slug"],
                        "payment-preferences"=> $input["payment_preferences"]  ?? "",
                        "bank-routing-number" => $input["bank_routing_number"]  ?? "",
                        "checking-account-number"=> $input["checking_account_number"]  ?? "",
                        "paypal-email"=> $input["paypal_email"]  ?? "", 
                        "zelle-email"=> $input["zelle_email"]  ?? "",
                        "zelle-phone-number"=> $input["zelle_phone_number"] ?? "",
                    ];

                    $paymentUpdate = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('PAYMENT')."/items/".$paymentId, ['fieldData' => $paymentsField, "isArchived" => false,
                    "isDraft" => false]);

                    if($paymentUpdate->successful()){
                        $customerField = [
                            "name" => $customer["fieldData"]["name"],
                            "slug" => $customer["fieldData"]["slug"],
                            "payment-option" => $paymentUpdate['id'],
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
                            'message' => $paymentUpdate->json()
                        ], 404);
                    }
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Referral ID not found'
                    ], 404);
                }
            } else {
                $paymentsField = [
                    "name"=> $input["payment_preferences"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    "payment-preferences"=> $input["payment_preferences"]  ?? "",
                    "bank-routing-number" => $input["bank_routing_number"]  ?? "",
                    "checking-account-number"=> $input["checking_account_number"]  ?? "",
                    "paypal-email"=> $input["paypal_email"]  ?? "", 
                    "zelle-email"=> $input["zelle_email"]  ?? "",
                    "zelle-phone-number"=> $input["zelle_phone_number"] ?? "",
                ];

                $paymentCreate = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('PAYMENT')."/items/", ['fieldData' => $paymentsField, "isArchived" => false,
                "isDraft" => false]);

                if($paymentCreate->successful()){
                    $customerField = [
                        "name" => $customer["fieldData"]["name"],
                        "slug" => $customer["fieldData"]["slug"],
                        "payment-option" => $paymentCreate['id']
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
                        'message' => $paymentCreate->json()
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
