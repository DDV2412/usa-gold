<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReferralDetail extends Controller
{
    public function index(Request $request, $customer_id) {

        $tokenApi = env('WEBFLOW_API');
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('CUSTOMER') . "/items/" .$customer_id);
    
        if ($response->successful()) {
            if(isset($response["items"]["0"]["referrals"])){
                $referral = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('REFERRALS') . "/items/" .$response["items"]["0"]["referrals"]);
        
                if($referral->successful()){
                    return response()->json([
                        'success' => true,
                        'data' => $referral->json()
                    ], 200);
                }else{
                    return response()->json([
                        'success' => false,
                        'data' => 'Referral not found'
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
