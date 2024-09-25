<?php

require_once 'bootstrap.php';

$data = [
    // The unique identifier for the transaction as returned by the iPay API upon the initial transaction request.
    'orderId' => '111aa4b9-baeb-4676-bbde-c5e4d5070cce',
    // Internally generated ID that is linked to the payment page. Is a temporary token, with a validity of 10 minutes.
    'token'  => '111aa4b9baeb4324324',
    // The language setting.
    'language' => 'en'
];

$iPayClient = new \BTransilvania\Api\IPayClient();

$result = $iPayClient->getFinishedPaymentInfo($data);

if ($result->isSuccess()) {
    echo "Get Finished Payment Info initiated successfully. Customer Message: " . $result->getActionCodeDescription();
} else {
    echo "Get Finished Payment Info initiation failed: " . $result->getActionCodeDescription();
}
