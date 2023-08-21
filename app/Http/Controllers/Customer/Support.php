<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class Support extends Controller
{
    public function index(Request $request, $customer_id)
    {
        $tokenApi = env('WEBFLOW_API');
        $customer = Http::withHeaders([
            'Authorization' => 'Bearer ' . $tokenApi,
        ])->timeout(30)->get("https://api.webflow.com/collections/".env('CUSTOMER')."/items/".$customer_id);


        if ($customer->successful()) {
            try {
                $file = $request->file('file-upload');
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
    
                $supportField = [
                    "name"=> $request["requestPack"],
                    "slug" => Str::slug(uniqid() . '-' . mt_rand(100000, 999999)),
                    "estimation"=> $request["estimation"],
                    "sending" => $request["sending"] ?? "",
                    "description"=> $request["description"],
                    "negotiable"=> $request["negotiable"],
                    "customer"=> $customer_id,
                    "file-upload"=> $documentUrl,
                    "_archived" => false,
                    "_draft" => false,
                ];

                $responseDocument = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $tokenApi,
                ])->timeout(30)->post("https://api.webflow.com/collections/".env('SUPPORT')."/items", ['fields' => $supportField]);
    
                if ($responseDocument->successful()) {
                    return response()->json([
                        'success' => true,
                        'data' => $responseCustomer->json()
                    ], 200);
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

        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
}
