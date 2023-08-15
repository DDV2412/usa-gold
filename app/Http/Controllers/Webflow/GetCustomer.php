<?php

namespace App\Http\Controllers\Webflow;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GetCustomer extends Controller
{
    public function index(Request $request){
        $query = $request->query('query');
        $input = $request->all();
        $tokenApi = env('WEBFLOW_API');
        $siteId = env('SITE_ID');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->get("https://api.webflow.com/collections/64c9deffefe08e9b2651414d/items");
        
        if ($response->successful()) {
            $data = json_decode($response->body()); 
            $groupedData = [];

            foreach ($data->items as $item) {
                $email = $item->email;

                // Jika query ada dan email cocok dengan query
                if ($query && $email === $query) {
                    if (!isset($groupedData[$email])) {
                        $groupedData[$email] = [
                            'email' => $email,
                            'items' => [],
                        ];
                    }

                    $requestPageId = $item->{'request-gold-pack'};

                    $requestPackResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->get("https://api.webflow.com/collections/64c9aca1ed6a63c07a9eaa8e/items/{$requestPageId}");

                    if ($requestPackResponse->successful()) {
                        $responseRequestData = json_decode($requestPackResponse->body());

                        $item->{'request-gold-pack'} = $responseRequestData->items;
                    }

                    $groupedData[$email]['items'][] = $item;
                }
            }
        
            return $groupedData; 
        } else {
            echo $response->body();
        }
    }
}
