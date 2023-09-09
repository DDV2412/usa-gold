<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Statistic extends Controller
{
    public function index($customer_id) {
        $tokenApi = env('WEBFLOW_API');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('CUSTOMER') . "/items/" .$customer_id);
        
        if ($response->successful()) {
            $customerData = $response->json();
            $requestGoldPacks = $customerData["fieldData"]["request-gold-packs"];
    
            $totalGoldPacks = 0;
            $incompleteCount = 0;
            $completeCount = 0;
            $totalOffers = 0;
    
            $goldPackData = [];
            
            foreach ($requestGoldPacks as $goldPack) {
                $goldPackResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('GOLDPACK') . "/items/" . $goldPack);
        
                if ($goldPackResponse->successful()) {
                    $goldPackData[] = $goldPackResponse->json();
                }
            }


            foreach ($goldPackData as $goldPack) {
   
                if ($goldPack["fieldData"]["order-status"] != "Complete") {
                    $incompleteCount++;
                } else {
                    $completeCount++;
                }

                // Check if "offers" field is a string
                if (isset($goldPack["fieldData"]["offers"])) {
                    if (is_string($goldPack["fieldData"]["offers"])) {
                        $offersArray = explode(",", $goldPack["fieldData"]["offers"]);
                        $totalOffers += count($offersArray);
                    } elseif (is_array($goldPack["fieldData"]["offers"])) {
                        $totalOffers += count($goldPack["fieldData"]["offers"]);
                    }
                }
            }
    
            return response()->json([
                'success' => true,
                'data' => [
                    'totalGoldPacks' => count($goldPackData),
                    'incompleteCount' => $incompleteCount,
                    'completeCount' => $completeCount,
                    'totalOffers' => $totalOffers,
                ]
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
       
    
}
