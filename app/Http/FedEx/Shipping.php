<?php
/**
 * This test will send the same test data as in FedEx's documentation:
 * /php/RateAvailableServices/RateAvailableServices.php5
 */

//remember to copy example.credentials.php as credentials.php replace 'FEDEX_KEY', 'FEDEX_PASSWORD', 'FEDEX_ACCOUNT_NUMBER', and 'FEDEX_METER_NUMBER'

namespace App\Http\FedEx;

use FedEx\ShipService;
use FedEx\ShipService\ComplexType;
use FedEx\ShipService\SimpleType;

class Shipping{

    protected $data;
    protected $customer_references;

    public function __construct($data, $customer_references){

        $this->data = $data;
        $this->customer_references = $customer_references;

    }
    
    function shipping(){
        $userCredential = new ComplexType\WebAuthenticationCredential();
        $userCredential
            ->setKey(env('FEDEX_KEY'))
            ->setPassword(env('FEDEX_PASSWORD'));

        $webAuthenticationDetail = new ComplexType\WebAuthenticationDetail();
        $webAuthenticationDetail->setUserCredential($userCredential);

        $clientDetail = new ComplexType\ClientDetail();
        $clientDetail
            ->setAccountNumber(env('FEDEX_ACCOUNT_NUMBER'))
            ->setMeterNumber(env('FEDEX_METER_NUMBER'));

        $version = new ComplexType\VersionId();
        $version
            ->setMajor(23)
            ->setIntermediate(0)
            ->setMinor(0)
            ->setServiceId('ship');

        $addressLines = $this->data['address'];

        if (!empty($this->data['app_unit'])) {
            $addressLines = $this->data['address'] . " " . $this->data['app_unit'];
        }

        $shipperAddress = new ComplexType\Address();
        $shipperAddress
            ->setStreetLines($addressLines)
            ->setCity($this->data['city'])
            ->setStateOrProvinceCode($this->data['state'])
            ->setPostalCode($this->data['zip'])
            ->setCountryCode('US');

        $shipperContact = new ComplexType\Contact();
        $shipperContact
            ->setEMailAddress($this->data['email'])
            ->setPersonName($this->data['first_name'] . ' ' . $this->data['last_name'])
            ->setPhoneNumber(($this->data['phone_number']));

        $shipper = new ComplexType\Party();
        $shipper
            ->setAccountNumber(env('FEDEX_ACCOUNT_NUMBER'))
            ->setAddress($shipperAddress)
            ->setContact($shipperContact);

        $recipientAddress = new ComplexType\Address();
        $recipientAddress
            ->setStreetLines(['11810 QUEENS BLVD'])
            ->setCity('Forest Hills')
            ->setStateOrProvinceCode('NY')
            ->setPostalCode('11375')
            ->setCountryCode('US');

        $recipientContact = new ComplexType\Contact();
        $recipientContact
            ->setPersonName('ERIC AKILOV')
            ->setCompanyName('BCFG PROCESSING')
            ->setPhoneNumber('(800) 337-7706');

        $recipient = new ComplexType\Party();
        $recipient
            ->setAddress($recipientAddress)
            ->setContact($recipientContact);

        $labelSpecification = new ComplexType\LabelSpecification();
        $labelSpecification
            ->setLabelStockType(new SimpleType\LabelStockType(SimpleType\LabelStockType::_PAPER_7X4POINT75))
            ->setImageType(new SimpleType\ShippingDocumentImageType(SimpleType\ShippingDocumentImageType::_PNG))
            ->setLabelFormatType(new SimpleType\LabelFormatType(SimpleType\LabelFormatType::_COMMON2D));
        $reff[] = array('CustomerReferenceType' => 'CUSTOMER_REFERENCE', 'Value' => $this->customer_references);

        $packageLineItem1 = new ComplexType\RequestedPackageLineItem();
        $packageLineItem1
            ->setSequenceNumber(1)
            ->setCustomerReferences($reff)
            ->setItemDescription('Product description')
            ->setWeight(new ComplexType\Weight(array(
                'Value' => 0.50,
                'Units' => SimpleType\WeightUnits::_LB
        )));

        $shippingChargesPayor = new ComplexType\Payor();
        $shippingChargesPayor->setResponsibleParty($shipper);

        $shippingChargesPayment = new ComplexType\Payment();
        $shippingChargesPayment
            ->setPaymentType(SimpleType\PaymentType::_RECIPIENT)
            ->setPayor($shippingChargesPayor);

        $requestedShipment = new ComplexType\RequestedShipment();
        $requestedShipment->setShipTimestamp(date('c'));
        $requestedShipment->setDropoffType(new SimpleType\DropoffType(SimpleType\DropoffType::_REGULAR_PICKUP));
        // $requestedShipment->setServiceType(new SimpleType\ServiceType(SimpleType\ServiceType::_FEDEX_EXPRESS_SAVER));
        $requestedShipment->setServiceType(new SimpleType\ServiceType(SimpleType\ServiceType::_STANDARD_OVERNIGHT));
        // $requestedShipment->setPackagingType(new SimpleType\PackagingType(SimpleType\PackagingType::_YOUR_PACKAGING));
        $requestedShipment->setPackagingType(new SimpleType\PackagingType(SimpleType\PackagingType::_FEDEX_SMALL_BOX));
        $requestedShipment->setShipper($shipper);
        $requestedShipment->setRecipient($recipient);
        $requestedShipment->setLabelSpecification($labelSpecification);
        $requestedShipment->setRateRequestTypes(array(new SimpleType\RateRequestType(SimpleType\RateRequestType::_PREFERRED)));
        $requestedShipment->setPackageCount(1);
        $requestedShipment->setRequestedPackageLineItems([
            $packageLineItem1
        ]);

        /* New lines start */
        $specialServicesRequested = new ComplexType\ShipmentSpecialServicesRequested();
        $specialServicesRequested->setSpecialServiceTypes([SimpleType\ShipmentSpecialServiceType::_FEDEX_ONE_RATE]);
        $requestedShipment->setSpecialServicesRequested($specialServicesRequested);
        /* New lines end */

        $requestedShipment->setShippingChargesPayment($shippingChargesPayment);
        $processShipmentRequest = new ComplexType\ProcessShipmentRequest();
        $processShipmentRequest->setWebAuthenticationDetail($webAuthenticationDetail);
        $processShipmentRequest->setClientDetail($clientDetail);
        $processShipmentRequest->setVersion($version);
        $processShipmentRequest->setRequestedShipment($requestedShipment);

        $shipService = new ShipService\Request();
        // live producation url
        $shipService->getSoapClient()->__setLocation('https://ws.fedex.com:443/web-services/ship');
        $result = $shipService->getProcessShipmentReply($processShipmentRequest);
        $error = array();
        foreach ($result->Notifications as $key => $value) {
        $error[] = $value->toArray();
        }

        return $result;
    }
}