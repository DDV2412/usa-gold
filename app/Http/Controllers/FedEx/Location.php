<?php

namespace App\Http\Controllers\FedEx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\FedEx\LocationService;

class Location extends Controller
{
    public function index(Request $request){
        $input = $request->all();

        $fedExLocation = new LocationService($input);
        $resultLocation = $fedExLocation->getLocation();

        $addressField = [];

        if (!empty($resultLocation)) {       
            foreach ($resultLocation->DistanceAndLocationDetails as $location) {

        
                $address = [
                    "street" => $location->LocationDetail->LocationContactAndAddress->Address->StreetLines,
                    "city" => $location->LocationDetail->LocationContactAndAddress->Address->City,
                    "state" => $location->LocationDetail->LocationContactAndAddress->Address->StateOrProvinceCode,
                    "zip" => $location->LocationDetail->LocationContactAndAddress->Address->PostalCode,
                    "distance" => $location->Distance->Value . ' ' . $location->Distance->Units,
                    "description" => $location->LocationDetail->LocationContactAndAddress->Contact->CompanyName,
                ];
        
                $addressField[] = $address;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $addressField
        ], 200);
    }
}
