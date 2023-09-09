<?php

namespace App\Http\Controllers\FedEx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\FedEx\Shipping;
use Illuminate\Support\Facades\Http;
use App\Helpers\BarcodeGenerator;


class CreateNewLabel extends Controller
{
    public function index($customer_id)
    {
        $tokenApi = env('WEBFLOW_API');

        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            
            $input = [
                "first_name"=> $customer["fieldData"]["name"],
                "last_name"=> $customer["fieldData"]["last-name"],
                "email"=> $customer["fieldData"]["email"],
                "phone_number"=> $customer["fieldData"]["phone-number"],
                "address"=> $customer["fieldData"]["address"],
                "unit_app"=> $customer["fieldData"]["unit-app"] ?? "",
                "city"=> $customer["fieldData"]["city"],
                "state"=> $customer["fieldData"]["state"],
                "zip"=> $customer["fieldData"]["zip"]
            ];
            // Create FedEx Shipping
            
            $fedExShipping = new Shipping($input, $customer["fieldData"]["reff"]);
            $result = $fedExShipping->shipping();

            

            // Cek apakah ada error
            if (!empty($result->Notifications)) {
                $error = [];
                $success = true; // Default success to true
                foreach ($result->Notifications as $notification) {
                    $error[] = $notification->toArray();

                    if ($notification->Severity === 'ERROR') {
                        $success = false; // If an error notification is found, set success to false
                    }
                }
                if (!$success) {
                    return response()->json(['success' => false, 'error' => $error], 400);
                }
            }

            $trackingID = $result->CompletedShipmentDetail->MasterTrackingId->TrackingNumber;
            $labelImageContent = $result->CompletedShipmentDetail->CompletedPackageDetails[0]->Label->Parts[0]->Image;

            
            // Store Label Image
            $randomFileName = uniqid() . '.png';
            Storage::disk('public')->put('labels/' . $randomFileName, $labelImageContent);

            

            // Mengambil URL dari penyimpanan publik
            $imageUrl = asset('storage/labels/' . $randomFileName);;

            

            $text = [
                'unique' => $customer["fieldData"]["reff"] . ' ' . Carbon::now()->toIso8601String(),
                'text' => $customer["fieldData"]["reff"] . ' ' . $customer["fieldData"]["name"] . ' ' .  $customer["fieldData"]["last-name"] . Carbon::now()->toIso8601String()
            ];

            

   
            



            $barcodeGenerator = new BarcodeGenerator($text);

            // Generate barcode dan dapatkan URL gambar
            $barcodeUrl = $barcodeGenerator->generateUrl();


            $labelField = [
                "name" => $customer["fieldData"]["name"],
                "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                "last-name" =>$customer["fieldData"]["last-name"],
                "email" => $customer["fieldData"]["email"],
                "phone-number" => $customer["fieldData"]["phone-number"],
                "address" => $customer["fieldData"]["address"],
                "unit-app" => $customer["fieldData"]["unit-app"] ?? "",
                "city" => $input["city"],
                "state" => $customer["fieldData"]["state"],
                "zip" => $customer["fieldData"]["zip"],
                "reff" => $customer["fieldData"]["reff"],
                "date-request" => Carbon::now()->toIso8601String(),
                "track-package" => $trackingID,
                "order-status" => 'Kit Request',
                "label" => $imageUrl,
                "barcode" => $barcodeUrl
            ];

            $responseLabel = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenApi,
            ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('GOLDPACK')."/items", ['fieldData' => $labelField, "isArchived" => false, "isDraft" => false]);

            if ($responseLabel->successful()) {

                $existingRequestGoldPacks = $customer["fieldData"]['request-gold-packs'] ?? []; // Mengambil array yang sudah ada atau menggunakan array kosong jika belum ada
               

                $customerField["fieldData"]['request-gold-packs'] = array_merge($existingRequestGoldPacks, [$responseLabel['id']]);
                //    Update Customer
                $customerField = [
                    "name" => $customer["fieldData"]["name"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    'request-gold-packs' => $customerField["fieldData"]['request-gold-packs']
                ];

                $responseCustomer = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fieldData' => $customerField, "isArchived" => false, "isDraft" => false]);

                if ($responseCustomer->successful()) {
                    return response()->json([
                        'success' => true,
                        'data' => $responseLabel->json()
                    ], 200);
                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Please check your input address or email'
                    ], 400);
                }

            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Please check your input address or email'
                ], 400);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
}
