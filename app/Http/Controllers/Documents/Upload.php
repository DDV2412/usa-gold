<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class Upload extends Controller
{

    public function index(Request $request, $customer_id)
    {

        $tokenApi = env('WEBFLOW_API');
        // Validasi berkas
        $validator = Validator::make($request->all(), [
            'document' => 'required|mimes:pdf,png,jpg|max:2048',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->get("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id);

        if ($customer->successful()) {
            try {
                $file = $request->file('document');
                // Pemeriksaan apakah berkas ada atau tidak
                if (!$file) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No file uploaded'
                    ], 400);
                }
    
                // Membuat nama berkas baru
                $originalFileName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $randomFileName = uniqid();
                $newFileName = "$randomFileName.$extension";
    
                // Menyimpan berkas
                $subDirectory = "documents";
                Storage::disk('public')->putFileAs($subDirectory, $file, $newFileName);
    
                // URL berkas
                $documentUrl = asset("storage/$subDirectory/$newFileName");

                $documentType = $request["document_type"];
    
                $documentField = [
                    "name" => $originalFileName,
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    "document-type" => $documentType,
                    "document-link" => $documentUrl,
                    "_archived" => false,
                    "_draft" => false,
                ];

                $responseDocument = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/collections/".env('DOCUMENT')."/items", ['fields' => $documentField]);
    
                if ($responseDocument->successful()) {
                    $existingDocument = $customer["items"][0]['document-uploads'] ?? []; // Mengambil array yang sudah ada atau menggunakan array kosong jika belum ada
                    $customerField['document-uploads'] = array_merge($existingDocument, [$responseDocument['_id']]);
                    //    Update Customer
                    $customerField = [
                        "name" => $customer["items"][0]["name"],
                        "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                        'document-uploads' => $customerField['document-uploads'],
                        "_archived" => false,
                        "_draft" => false,
                    ];

                    $responseCustomer = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $tokenApi,
                    ])->timeout(30)->put("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id, ['fields' => $customerField]);

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
                }else{
                    return response()->json([
                        'success' => false,
                        'error' => $responseDocument->json()
                    ], 500);
                }

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => "An error occurred while uploading the document"
                ], 500);
            }

        }else{
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

}
