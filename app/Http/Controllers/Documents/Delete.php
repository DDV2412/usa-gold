<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class Delete extends Controller
{
    public function index(Request $request, $file)
    {
    
        $newFileName = $file; // Ganti dengan nama berkas yang sesuai
        $subDirectory = "documents";
        $fullFilePath = Storage::disk('public')->path("$subDirectory/$newFileName");

        if (file_exists($fullFilePath)) {
            unlink($fullFilePath);
            return response()->json(['message' => 'File deleted successfully']);
        } else {
            return response()->json(['message' => 'File not found'], 404);
        }


    }
}
