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
                "first_name"=> $customer["items"][0]["name"],
                "last_name"=> $customer["items"][0]["last-name"],
                "email"=> $customer["items"][0]["email"],
                "phone_number"=> $customer["items"][0]["phone-number"],
                "address"=> $customer["items"][0]["address"],
                "unit_app"=> $customer["items"][0]["unit-app"] ?? "",
                "city"=> $customer["items"][0]["city"],
                "state"=> $customer["items"][0]["state"],
                "zip"=> $customer["items"][0]["zip"]
            ];
            // Create FedEx Shipping
            $fedExShipping = new Shipping($input, $customer["items"][0]["reff"]);
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
                'unique' => $customer["items"][0]["reff"] . ' ' . Carbon::now()->toIso8601String(),
                'text' => $customer["items"][0]["reff"] . ' ' . $customer["items"][0]["name"] . ' ' .  $customer["items"][0]["last-name"] . Carbon::now()->toIso8601String()
            ];

   



            $barcodeGenerator = new BarcodeGenerator($text);

            // Generate barcode dan dapatkan URL gambar
            $barcodeUrl = $barcodeGenerator->generateUrl();


            $labelField = [
                "name" => $customer["items"][0]["name"],
                "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                "last-name" =>$customer["items"][0]["last-name"],
                "email" => $customer["items"][0]["email"],
                "phone-number" => $customer["items"][0]["phone-number"],
                "address" => $customer["items"][0]["address"],
                "unit-app" => $customer["items"][0]["unit-app"] ?? "",
                "city" => $input["city"],
                "state" => $customer["items"][0]["state"],
                "zip" => $customer["items"][0]["zip"],
                "reff" => $customer["items"][0]["reff"],
                "date-request" => Carbon::now()->toIso8601String(),
                "track-package" => $trackingID,
                "order-status" => 'Kit Request',
                "label" => $imageUrl,
                "barcode" => $barcodeUrl,
                "_archived" => false,
                "_draft" => false,
            ];

            $responseLabel = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenApi,
            ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('GOLDPACK')."/items", ['fields' => $labelField]);

            if ($responseLabel->successful()) {

                $existingRequestGoldPacks = $customer["items"][0]['request-gold-packs'] ?? []; // Mengambil array yang sudah ada atau menggunakan array kosong jika belum ada
               

                $customerField['request-gold-packs'] = array_merge($existingRequestGoldPacks, [$responseLabel['_id']]);
                //    Update Customer
                $customerField = [
                    "name" => $customer["items"][0]["name"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    'request-gold-packs' => $customerField['request-gold-packs'],
                    "_archived" => false,
                    "_draft" => false,
                ];

                $responseCustomer = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->put("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/".$customer_id, ['fields' => $customerField]);

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
