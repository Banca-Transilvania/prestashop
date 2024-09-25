<?php

require_once 'bootstrap.php';

$data = [
    // The unique identifier for the transaction as returned by the iPay API upon the initial transaction request.
    'orderId' => '111aa4b9-baeb-4676-bbde-c5e4d5070cce',
    'amount'  => '1200'
];

$iPayClient = new \BTransilvania\Api\IPayClient(
    [
        'user'         => '*****', // Replace '*****' with your actual iPay user.
        'password'     => '*****', // Replace '*****' with your actual iPay password.
        'environment'  => 'test', // Assuming a test environment.
        'platformName' => 'Magento - Community', // As specified in your example.
        'language'     => 'en' // The language setting.
    ]
);

$result = $iPayClient->deposit($data);

if ($result->isSuccess()) {
    echo "Capture initiated successfully. Customer Message: " . $result->getActionCodeDescription();
} else {
    echo "Capture initiation failed: " . $result->getErrorMessage();
}