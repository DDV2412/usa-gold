<?php
/**
 * This test will send the same test data as in FedEx's documentation:
 * /php/RateAvailableServices/RateAvailableServices.php5
 */

//remember to copy example.credentials.php as credentials.php replace 'FEDEX_KEY', 'FEDEX_PASSWORD', 'FEDEX_ACCOUNT_NUMBER', and 'FEDEX_METER_NUMBER'

namespace App\Http\FedEx;

use FedEx\LocationsService\Request;
use FedEx\LocationsService\ComplexType;
use FedEx\LocationsService\SimpleType;

class LocationService{

    protected $data;

    public function __construct($data){
        $this->data = $data;
    }

    function location(){
        $searchLocationsRequest = new ComplexType\SearchLocationsRequest();

        // Authentication & client details.
        $searchLocationsRequest->WebAuthenticationDetail->UserCredential->Key = env('FEDEX_KEY');
        $searchLocationsRequest->WebAuthenticationDetail->UserCredential->Password = env('FEDEX_PASSWORD');
        $searchLocationsRequest->ClientDetail->AccountNumber = env('FEDEX_ACCOUNT_NUMBER');
        $searchLocationsRequest->ClientDetail->MeterNumber = env('FEDEX_METER_NUMBER');


        // Version.
        $searchLocationsRequest->Version->ServiceId = 'locs';
        $searchLocationsRequest->Version->Major = 9;
        $searchLocationsRequest->Version->Intermediate = 0;
        $searchLocationsRequest->Version->Minor = 0;

        // Locations search criterion.
        $searchLocationsRequest->LocationsSearchCriterion = SimpleType\LocationsSearchCriteriaType::_ADDRESS;

        $addressLines = $this->data['address'];

        if (!empty($this->data['unit_app'])) {
            $addressLines = $this->data['address'] . " " . $this->data['unit_app'];
        }

        // Address
        $searchLocationsRequest->Address->StreetLines = $addressLines;
        $searchLocationsRequest->Address->City = $this->data['city'];
        $searchLocationsRequest->Address->StateOrProvinceCode = $this->data['state'];
        $searchLocationsRequest->Address->PostalCode = $this->data['zip'];
        $searchLocationsRequest->Address->CountryCode = 'US';

        // Multiple matches action.
        $searchLocationsRequest->MultipleMatchesAction = SimpleType\MultipleMatchesActionType::_RETURN_ALL;

        // Get Search Locations reply.
        $locationServiceRequest = new Request();
        $searchLocationsReply = $locationServiceRequest->getSearchLocationsReply($searchLocationsRequest);

        if (empty($searchLocationsReply->AddressToLocationRelationships[0]->DistanceAndLocationDetails)) {
            return;
        }

        return $searchLocationsReply->AddressToLocationRelationships[0]->DistanceAndLocationDetails;
        
    }
    
}