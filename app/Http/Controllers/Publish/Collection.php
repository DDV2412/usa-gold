<?php

namespace App\Http\Controllers\Publish;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Collection extends Controller
{
    public function index($collection_id) {
        $tokenApi = env('WEBFLOW_API');

        $itemIds = [$collection_id];
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,'Content-Type' => 'application/json',
        ])->timeout(30)->put("https://api.webflow.com/beta/collections/".env('GOLDPACK')."/items/publish", ['itemIds' => $itemIds]);
        
        if ($response->successful()) {
            $customerData = $response->json();
    
            return response()->json([
                'success' => true,
                'data' => $customerData
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Collection ID not found"
            ], 404);
        }
    }
}
