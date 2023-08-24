<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeGenerator
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    function generateUrl()
    {
        $text = "{$this->data['unique']}";
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($text, $generator::TYPE_CODE_128);

        $barcode_image = imagecreatefromstring($barcode);
        $barcodeWidth = imagesx($barcode_image);
        $barcodeHeight = imagesy($barcode_image);

        // Menghitung panjang teks
        $textLength = strlen($this->data['text']);
        
        // Menghitung panjang total gambar (barcode + 20 padding di kiri dan kanan)
        $imageWidth = $barcodeWidth + (20 * 2); // Padding kiri dan kanan
        $imageHeight = $barcodeHeight + 50; // Space for text
        $newImage = imagecreatetruecolor($imageWidth, $imageHeight);

        // Set background color
        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $backgroundColor);

        // Copy barcode to the new image with padding
        imagecopy($newImage, $barcode_image, 16, 0, 0, 0, $barcodeWidth, $barcodeHeight);

        // Add text
        $textColor = imagecolorallocate($newImage, 0, 0, 0);
        $textWidth = imagefontwidth(5) * $textLength;
        $textX = ($imageWidth - $textWidth) / 2;
        $textY = $barcodeHeight + 16;
        imagestring($newImage, 16, $textX, $textY, $this->data['text'], $textColor);

        // Create a temporary image file
        $tempImagePath = tempnam(sys_get_temp_dir(), 'barcode');
        imagepng($newImage, $tempImagePath);

        // Generate a unique name for the barcode file
        $barcodeName = uniqid() . '.png';

        // Save the image to Laravel storage
        Storage::disk('public')->put('barcode/' . $barcodeName, file_get_contents($tempImagePath));

        // Delete the temporary file
        unlink($tempImagePath);

        // Get the URL of the stored image
        $imageUrl = asset('storage/barcode/' . $barcodeName);

        return $imageUrl;
    }
}
