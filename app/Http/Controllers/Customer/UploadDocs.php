<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UploadDocs extends Controller
{
    public function index($customer_id) {
        $tokenApi = env('WEBFLOW_API');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/collections/" . env('CUSTOMER') . "/items/" .$customer_id);
        
        if ($response->successful()) {
            $customerData = $response->json();

            if(isset($customerData["items"][0]["document-uploads"])){
                $requestDocsIds = $customerData["items"][0]["document-uploads"];
    
                $documentData = [];
                foreach ($requestDocsIds as $docsId) {
                    $docsResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->get("https://api.webflow.com/collections/" . env('DOCUMENT') . "/items/" . $docsId);
            
                    if ($docsResponse->successful()) {
                        $documentData[] = $docsResponse->json();
                    }
                }
        
                $customerData["items"][0]["document-uploads"] = $documentData;
        
                return response()->json([
                    'success' => true,
                    'data' => $customerData["items"][0]
                ], 200);
            }else{
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }
            
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    
}