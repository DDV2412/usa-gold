<?php

namespace App\Http\Controllers\FedEx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\FedEx\Shipping;

class RequestLabel extends Controller
{
    public function index(Request $request)
    {
        $input = $request->all();
        $tokenApi = env('WEBFLOW_API');
        $siteId = env('SITE_ID');

        // Invite User
        $userEmail = [
            "email" => $input["email"],
        ];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->post("https://api.webflow.com/sites/${siteId}/users/invite", $userEmail);
        
        if ($response->successful()) {
           // echo $response->status();  
        } else {
            echo $response->body(); 
        }
        

        $customer_references = strval(rand(1000, 9999));

        // Create FedEx Shipping
        $fedExShipping = new Shipping($input, $customer_references);
        $result = $fedExShipping->shipping();
        $trackingID = $result->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
        $labelImageContent = $result->CompletedShipmentDetail->CompletedPackageDetails[0]->Label->Parts[0]->Image;

        // Store Label Image
        $randomFileName = uniqid() . '.png';
        Storage::disk('local')->put('label_images/' . $randomFileName, $labelImageContent);
        $imageUrl = Storage::url('label_images/' . $randomFileName);

        // Post Request Label
        $urlRequest = "https://api.webflow.com/collections/64c9aca1ed6a63c07a9eaa8e/items";
        $fields = [
            "slug" => Str::slug(uniqid() . '-' . Str::random(8)),
            "name" => $input["first_name"],
            "last-name" => $input["last_name"],
            "email" => $input["email"],
            "track-package" => $trackingID,
            "label-link" => url($imageUrl),
            "customer-references" => $customer_references,
            "_archived" => false,
            "_draft" => false,
        ];
        $requestGold = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->post($urlRequest, ['fields' => $fields]);

        if ($requestGold->successful()) {
           // echo $requestGold->status();
            $responseData = json_decode($requestGold->body(), true);

            // Post Customer Detail
            $urlCustomer = "https://api.webflow.com/collections/64c9deffefe08e9b2651414d/items";
            $fieldCustomer = [
                "slug" => Str::slug(uniqid() . '-' . Str::random(8)),
                "name" => $input["first_name"],
                "last-name" => $input["last_name"],
                "email" => $input["email"],
                "address" => $input["address"],
                "phone-number" => $input["phone_number"],
                "city" => $input["city"],
                "state" => $input["state"],
                "zip" => $input["zip"],
                "date-request" => Carbon::now()->toIso8601String(),
                "request-gold-pack" => $responseData['_id'],
                "_archived" => false,
                "_draft" => false,
            ];
            $responseCustomer = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenApi,
            ])->post($urlCustomer, ['fields' => $fieldCustomer]);

            if ($responseCustomer->successful()) {
              //  echo $responseCustomer->status();
            } else {
                echo $responseCustomer->body();
            }
        } else {
            echo $requestGold->body();
        }

        return response()->json(['success' => true], 200);
    }
}
