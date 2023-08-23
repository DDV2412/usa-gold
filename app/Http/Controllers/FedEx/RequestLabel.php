<?php

namespace App\Http\Controllers\FedEx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Http\FedEx\Shipping;
use App\Helpers\BarcodeGenerator;

class RequestLabel extends Controller
{
    public function index(Request $request)
    {
        $input = $request->all();
        $tokenApi = env('WEBFLOW_API');
        $siteId = env('SITE_ID');
        $customer_references = strval(rand(1000, 9999));
        

        // Create FedEx Shipping
        $fedExShipping = new Shipping($input, $customer_references);
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
        $imageUrl = asset('storage/labels/' . $randomFileName);


        $userEmail = [
            "email" => $input["email"],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->post("https://api.webflow.com/sites/${siteId}/users/invite", $userEmail);
        
        if ($response->successful()) {


            $barcodeGenerator = new BarcodeGenerator($input);

            // Generate barcode dan dapatkan URL gambar
            $barcodeUrl = $barcodeGenerator->generateUrl();
            
            //    Create Request
            $labelField = [
                "name" => $input["first_name"],
                "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                "last-name" => $input["last_name"],
                "email" => $input["email"],
                "phone-number" => $input["phone_number"],
                "address" => $input["address"],
                "unit-app" => $input["unit_app"],
                "city" => $input["city"],
                "state" => $input["state"],
                "zip" => $input["zip"],
                "reff" => $customer_references,
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
            ])->timeout(30)->post("https://api.webflow.com/collections/".env('GOLDPACK')."/items", ['fields' => $labelField]);

            if ($responseLabel->successful()) {
                //    Create Customer
                $customerField = [
                    "name" => $input["first_name"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    "last-name" => $input["last_name"],
                    "email" => $input["email"],
                    "phone-number" => $input["phone_number"],
                    "address" => $input["address"],
                    "unit-app" => $input["unit_app"],
                    "city" => $input["city"],
                    "state" => $input["state"],
                    "zip" => $input["zip"],
                    "reff" => $customer_references,
                    'request-gold-packs' => [$responseLabel['_id']],
                    "_archived" => false,
                    "_draft" => false,
                ];

                $responseCustomer = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/collections/".env('CUSTOMER')."/items", ['fields' => $customerField]);

                if ($responseCustomer->successful()) {
                    return response()->json([
                        'success' => true,
                        'data' => $responseCustomer->json()
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
            if($response->status() === 409){
                $getAllCustomer = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->get("https://api.webflow.com/collections/".env('CUSTOMER')."/items");


                if ($getAllCustomer->successful()) {
                    $customer = null;
                    foreach ($getAllCustomer['items'] as $item) {
                        if ($item['email'] === $input["email"]) {
                            $customer = $item;
                            break;
                        }
                    }

                    if($customer === null){
                        return response()->json([
                            'success' => false,
                            'message' => 'Your email has been registered, please check your email contact and follow the instructions'
                        ], 400);
                    }

                    
                    

        
                    $barcodeGenerator = new BarcodeGenerator($input);

                    // Generate barcode dan dapatkan URL gambar
                    $barcodeUrl = $barcodeGenerator->generateUrl();

                    $labelField = [
                        "name" => $input["first_name"],
                        "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                        "last-name" => $input["last_name"],
                        "email" => $input["email"],
                        "phone-number" => $input["phone_number"],
                        "address" => $input["address"],
                        "unit-app" => $input["unit_app"],
                        "city" => $input["city"],
                        "state" => $input["state"],
                        "zip" => $input["zip"],
                        "reff" =>  $customer["reff"],
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
                    ])->timeout(30)->post("https://api.webflow.com/collections/".env('GOLDPACK')."/items", ['fields' => $labelField]);
        
                    if ($responseLabel->successful()) {

                        $existingRequestGoldPacks = $customer['request-gold-packs'] ?? []; // Mengambil array yang sudah ada atau menggunakan array kosong jika belum ada
                       

                        $customerField['request-gold-packs'] = array_merge($existingRequestGoldPacks, [$responseLabel['_id']]);
                        //    Update Customer
                        $customerField = [
                            "name" => $customer["name"],
                            "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                            'request-gold-packs' => $customerField['request-gold-packs'],
                            "_archived" => false,
                            "_draft" => false,
                        ];
        
                        $responseCustomer = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $tokenApi,
                        ])->timeout(30)->put("https://api.webflow.com/collections/".env('CUSTOMER')."/items/" . $customer["_id"], ['fields' => $customerField]);
        
                        if ($responseCustomer->successful()) {
                            return response()->json([
                                'success' => true,
                                'data' => $responseCustomer->json()
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
                        'message' => 'Please check your input address or email'
                    ], 400);
                }
            }
        }
    }
}
