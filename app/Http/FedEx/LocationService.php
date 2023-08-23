<?php

namespace App\Http\FedEx;

use SoapClient;
use SoapFault;

class LocationService
{
    protected $client;
    protected $data;

    public function __construct($data)
    {
        $this->client = new SoapClient(
            public_path('LocationService.wsdl'),
            array('trace' => 1)
        );

        $this->data = $data;
    }

    function getLocation()
    {
        $request_soap = array();

        // Populate $request_soap array with the required data
        $request_soap = array();

        $request_soap['WebAuthenticationDetail'] = array(
            'UserCredential' => array(
                'Key' => 'abbBMmuhPEZhiYEs',
                'Password' => 'kzYTfK5ifb49UDPm3m2Jod2p3',
            )
        );

        $request_soap['ClientDetail'] = array(
            'AccountNumber' => '790868110',
            'MeterNumber' => '252576244',
        );

        $request_soap['TransactionDetail'] = array('CustomerTransactionId' => '*** Search Locations request_soap using PHP ***');
        $request_soap['Version'] = array(
            'ServiceId' => 'locs',
            'Major' => '9',
            'Intermediate' => '0',
            'Minor' => '0'
        );
        $request_soap['EffectiveDate'] = date('Y-m-d');

        $addressLines = $this->data['address'];

        if (!empty($this->data['unit_app'])) {
            $addressLines = $this->data['address'] . " " . $this->data['unit_app'];
        }

        $bNearToPhoneNumber = false;
        if ($bNearToPhoneNumber) {
            $request_soap['LocationsSearchCriterion'] = 'PHONE_NUMBER';
        } else {
            $request_soap['LocationsSearchCriterion'] = 'ADDRESS';
            $request_soap['Address'] = array(
                'StreetLines'=> $addressLines,
                'City'=>$this->data['city'],
                'StateOrProvinceCode'=>$this->data['state'],
                'PostalCode'=>$this->data['zip'],
                'CountryCode'=>'US'
            );
        }

        $request_soap['MultipleMatchesAction'] = 'RETURN_ALL';
        $request_soap['SortDetail'] = array(
            'Criterion' => 'DISTANCE',
            'Order' => 'LOWEST_TO_HIGHEST'
        );
        $request_soap['Constraints'] = array(
            'RadiusDistance' => array(
                'Value' => 15.0,
                'Units' => 'MI'
            ),
            'ExpressDropOfTimeNeeded' => '15:00:00.00',
            'ResultFilters' => 'EXCLUDE_LOCATIONS_OUTSIDE_STATE_OR_PROVINCE',
            'RequiredLocationAttributes' => array(
                'ACCEPTS_CASH', 'ALREADY_OPEN'
            ),
            'ResultsRequested' => 5,
            'LocationContentOptions' => array('HOLIDAYS'),
            'LocationTypesToInclude' => array('FEDEX_AUTHORIZED_SHIP_CENTER', 'FEDEX_OFFICE')
        );
        $request_soap['DropoffServicesDesired'] = array(
            'Express' => 1, // Location desired services
            'FedExStaffed' => 1,
            'FedExSelfService' => 1,
            'FedExAuthorizedShippingCenter' => 1,
            'HoldAtLocation' => 1
        );


        try {
            if ($this->setEndpoint('changeEndpoint')) {
                $this->client->__setLocation($this->setEndpoint('endpoint'));
            }

            $response = $this->client->searchLocations($request_soap);

            if ($response->HighestSeverity != 'FAILURE' && $response->HighestSeverity != 'ERROR') {
                // echo '';
                // printString($response->TotalResultsAvailable, '', 'Total Locations Founds');
                // printString($response->ResultsReturned, '', 'Locations Returned');
                // printString('', '', 'Address Information Used for Search');
                // locationDetails($response->AddressToLocationRelationships->MatchedAddress, '');
                // printString('', '', 'LocationDetails');
                // locationDetails($response->AddressToLocationRelationships->DistanceAndLocationDetails, '');
                // echo '';

                // printSuccess($client, $response);

                return $response->AddressToLocationRelationships;
            } else {
                throw new \Exception('An error occurred: ' . $exception->getMessage());
            }
        } catch (SoapFault $exception) {
            // Handle SoapFault (return response or throw exception)
            // Contoh:
            throw new \Exception('An error occurred: ' . $exception->getMessage());
        }
    }

    protected function setEndpoint($type)
    {
        if($type == 'changeEndpoint') return true;
	    if($type == 'endpoint') return 'https://ws.fedex.com:443/web-services/';
    }
}
