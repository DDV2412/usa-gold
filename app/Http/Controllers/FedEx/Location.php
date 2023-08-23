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
            $addressResultMatch = $resultLocation->MatchedAddress;
        
            $mainAddressField = [
                "address" => $addressResultMatch->StreetLines,
                "city" => $input["city"],
                "state" => $addressResultMatch->StateOrProvinceCode,
                "zip" => $addressResultMatch->PostalCode,
                "match" => true,
            ];
        
            $addressField[] = $mainAddressField;
        
            foreach ($resultLocation->DistanceAndLocationDetails as $distanceAndLocationDetails) {
                $addressResult = $distanceAndLocationDetails->LocationDetail->LocationContactAndAddress->Address;
        
                $address = [
                    "address" => $addressResult->StreetLines,
                    "city" => $addressResult->City,
                    "state" => $addressResult->StateOrProvinceCode,
                    "zip" => $addressResult->PostalCode,
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
