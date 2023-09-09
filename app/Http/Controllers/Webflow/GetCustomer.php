<?php

namespace App\Http\Controllers\Webflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GetCustomer extends Controller
{
    public function index(Request $request) {
        $query = $request->query('email');
        $tokenApi = env('WEBFLOW_API');
    
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/" . env('CUSTOMER') . "/items");
    
        if ($response->successful()) {
            $data = $response->json();
            $groupedData = [];
    
            foreach ($data["items"] as $item) {
                $email = $item["fieldData"]["email"];
    
                // Jika query ada dan email cocok dengan query
                if ($query && $email === $query) {
                    if (!isset($groupedData[$email])) {
                        $groupedData[$email] = [
                            'email' => $email,
                            'items' => [],
                        ];
                    }
                    $groupedData[$email]['items'][] = $item;
                }
            }
    
            return response()->json([
                'success' => true,
                'data' => $groupedData
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
}
