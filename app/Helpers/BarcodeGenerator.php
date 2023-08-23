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
        $text = "{$this->data['reff']}";
        $generator = new BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($text, $generator::TYPE_CODE_128);

        $barcode_image = imagecreatefromstring($barcode);
        $barcodeWidth = imagesx($barcode_image);
        $barcodeHeight = imagesy($barcode_image);


        // Create a new image with barcode and text
        $imageWidth = $barcodeWidth;
        $imageHeight = $barcodeHeight + 50; // Space for text
        $newImage = imagecreatetruecolor($imageWidth, $imageHeight);

        // Set background color
        $backgroundColor = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $backgroundColor);

        // Copy barcode to the new image
        imagecopy($newImage, $barcode_image, 0, 0, 0, 0, $barcodeWidth, $barcodeHeight);

        // Add text
        $textColor = imagecolorallocate($newImage, 0, 0, 0);
        $textWidth = imagefontwidth(5) * strlen($this->data['text']);
        $textX = ($imageWidth - $textWidth) / 2;;
        $textY = $barcodeHeight + 16;
        imagestring($newImage, 16, $textX, $textY, $this->data['text'], $textColor);

        // Simpan gambar sementara dalam file lokal
        $tempImagePath = tempnam(sys_get_temp_dir(), 'barcode');
        imagepng($newImage, $tempImagePath);


        // Menghasilkan nama unik untuk berkas barcode
        $barcodeName = uniqid() . '.png';

        // Menyimpan gambar dalam penyimpanan Laravel
        Storage::disk('public')->put('barcode/' . $barcodeName, file_get_contents($tempImagePath));

        // Hapus file sementara
        unlink($tempImagePath);

        // Mengambil URL dari penyimpanan publik
        $imageUrl = asset('storage/barcode/' . $barcodeName);

        return $imageUrl;
    }

}
