<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoldPacks extends Controller
{
    public function index($customer_id) {
        $tokenApi = env('WEBFLOW_API');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('CUSTOMER') . "/items/" .$customer_id);
        
        if ($response->successful()) {
            $customerData = $response->json();
            $requestGoldPackIds = $customerData["fieldData"]["request-gold-packs"];
            
    
            $goldPackData = [];
            foreach ($requestGoldPackIds as $goldPackId) {
                $goldPackResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('GOLDPACK') . "/items/" . $goldPackId);
        
                if ($goldPackResponse->successful()) {
                    $goldPackData[] = $goldPackResponse->json();
                }
            }
    
            $customerData["fieldData"]["request-gold-packs"] = $goldPackData;
    
            return response()->json([
                'success' => true,
                'data' => $customerData["fieldData"]
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    
}
