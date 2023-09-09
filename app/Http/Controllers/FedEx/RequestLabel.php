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
        ])->timeout(30)->post("https://api.webflow.com/beta/sites/${siteId}/users/invite", $userEmail);
        
        if ($response->successful()) {

            $text = [
                'unique' => $customer_references . ' ' . Carbon::now()->toIso8601String(),
                'text' => $customer_references . ' ' . $input["first_name"] . ' ' . $input["last_name"] . ' ' . Carbon::now()->toIso8601String()
            ];


            $barcodeGenerator = new BarcodeGenerator($text);

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
                "barcode" => $barcodeUrl
            ];

            $responseLabel = Http::withHeaders([
                'Authorization' => 'Bearer ' . $tokenApi,
            ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('GOLDPACK')."/items", ['fieldData' => $labelField, "isArchived" => false, "isDraft" => false]);

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
                    'request-gold-packs' => [$responseLabel['id']]
                ];

                $responseCustomer = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items", ['fieldData' => $customerField, "isArchived" => false, "isDraft" => false]);

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
            if($response->status() === 409){
                $getAllCustomer = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->get("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items");


                if ($getAllCustomer->successful()) {
                    $customer = null;

                    foreach ($getAllCustomer['items'] as $item) {
                        if ($item['fieldData']['email'] === $input["email"]) {
                            $customer = $item;
                            break;
                        }
                    }                   

                    $text = [
                        'unique' => $customer['fieldData']["reff"] . ' ' . Carbon::now()->toIso8601String(),
                        'text' => $customer['fieldData']["reff"] . ' ' . $input["first_name"] . ' ' . $input["last_name"] . ' ' . Carbon::now()->toIso8601String()
                    ];
        
                    
        
                    $barcodeGenerator = new BarcodeGenerator($text);

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
                        "reff" =>  $customer['fieldData']["reff"],
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


                        $existingRequestGoldPacks = $customer['fieldData']['request-gold-packs'] ?? []; // Mengambil array yang sudah ada atau menggunakan array kosong jika belum ada
                       

                        $customerField['fieldData']['request-gold-packs'] = array_merge($existingRequestGoldPacks, [$responseLabel['id']]);
                        //    Update Customer
                        $customerField = [
                            "name" => $customer['fieldData']["name"],
                            "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                            'request-gold-packs' => $customerField['fieldData']['request-gold-packs']
                        ];
        
                        $responseCustomer = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $tokenApi,
                        ])->timeout(30)->patch("https://api.webflow.com/beta/collections/".env('CUSTOMER')."/items/" . $customer["id"], ['fieldData' => $customerField, "isArchived" => false, "isDraft" => false]);
        
                        if ($responseCustomer->successful()) {
                            return response()->json([
                                'success' => true,
                                'data' => $responseLabel->json()
                            ], 200);
                        }else{
                            return response()->json([
                                'success' => false,
                                'message' => $responseCustomer->json()
                            ], 400);
                        }
        
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => $responseLabel->json()
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
