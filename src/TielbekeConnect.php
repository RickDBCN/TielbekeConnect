<?php

namespace RickDB\Tielbeke;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

require '../vendor/autoload.php';

class TielbekeApi
{
    protected $barCode;
    protected $userName;
    protected $password;
    protected $apiKey;
    protected $baseUrl = 'https://api.weborderentry.net/api';


    public function __construct($userName, $password, $apiKey)
    {
        $this->userName = $userName;
        $this->password = $password;
        $this->apiKey = $apiKey;
    }

    protected function getUrl($endpoint): string
    {
        return $this->baseUrl . '/' . $endpoint;
    }

    public function getNewBarcode()
    {
        //todo: create new sscc barcode
    }

    public function getCheckDigit($barCode)
    {
        // strip off unessential 0s
        $len = strlen($barCode);


        // In case it is still the invalid length
//        if($len != 18 && $len != 17) {
//            return false;
//        }

        // @doc: For explanation see: http://www.gs1.org/how-calculate-check-digit-manually
        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $multiplier = ($i % 2 == 0) ? 3 : 1;
            $sum += intval($barCode{$i}) * $multiplier;
        }
        $checkDigit = (ceil($sum / 10) * 10) - $sum;

        if ($len == 17) {
            return $checkDigit;
        }
        if ($len == 10) {
            return $checkDigit == intval($barCode{17});
        }
        return false;
    }




    public function sendRequest($endpoint, $newOrder = false)
    {
        $client = new Client();

        $data = file_get_contents('demo.xml', true);
        if ($newOrder){
            try {
                $request = $client->request('POST', $this->getUrl($endpoint), [
                    [
                        'body' => $data
                    ],
                    'auth' => [
                        $this->userName,
                        $this->password
                    ],
                    'headers' => [
                        'w-API-key' => $this->apiKey,
                        'Content-Type' => 'application/xml'
                    ]

                ]);

                return json_decode($request->getBody()->getContents());
            }
            catch (GuzzleException $e) {
                $result = null;

                echo "Something went wrong uploading a new order: $e";
            }
        } else {
            try {
                $request = $client->request('GET', $this->getUrl($endpoint), [
                    'auth' => [
                        $this->userName,
                        $this->password
                    ],
                    'headers' => [
                        'w-API-key' => $this->apiKey
                    ]
                ]);
                $result = json_decode($request->getBody()->getContents(), JSON_FORCE_OBJECT);
            } catch (GuzzleException $e) {
                $result = null;
                echo "Something went wrong fetching the results:  $e";
            }

            if (!is_object($result)) {
                return $result;
            }
            return $result;
        }
    }

    public function checkFile() {

    }

    public function newOrder()
    {

        return $this->sendRequest('importorders', true);
    }

    public function getEta($vva)
    {
        $result = $this->sendRequest('eta?waybillnr=' . $vva);
        return $result['ETA'];
    }

    public function getShipmentStatus($vva)
    {
        $result = $this->sendRequest('shipment?waybillnr=' . $vva);
        return $result[0]['StatusZending'];
    }

    public function getShipmentVva($vva) {
        //  get all details of shipment
        return $this->sendRequest('shipment?waybillnr=' . $vva);
    }

    public function getShipmentDate($daterange) {
        return $this->sendRequest('shipment?daterange=' . $daterange);
    }

    public function getPod($vva) {
        return $this->sendRequest('pod?waybillnr=' . $vva);
    }

    public function getDeliveryDate($vva) {
        $result = $this->sendRequest('deliverydate?waybillnr=' . $vva);
        return $result;
    }

    public function isLabelPrinted($vva): ?bool
    {
        $result = $this->sendRequest('label?waybillnr=' . $vva);
        if($result->label_printed = 1) {
            return true;
        }
        elseif($result->label_printed = 0) {
            return false;
        }
        else {
            return null;
        }
    }

    public function splitAddress(string $currentStreet): array {
        $aMatch = array();
        $pattern = '#^([\w[:punct:] ]+) (\d{1,5})\s?([\w[:punct:]\-/]*)$#';
        preg_match($pattern, $currentStreet, $aMatch);
        $street = $aMatch[1] ?? $currentStreet;
        $number = $aMatch[2] ?? '';
        $numberAddition = $aMatch[3] ?? '';
        return array('street' => $street, 'number' => $number, 'numberAddition' => $numberAddition);
    }


}