<?php

require_once 'bootstrap.php';

$data = [
    // The specific order number from your request.
    'orderNumber' => '8042112'
];

// OR

$data2 = [
    // The unique identifier for the transaction as returned by the iPay API upon the initial transaction request.
    'orderId' => 'c4f617e2-edf0-4c23-9408-4d3129f751d8'
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

$result = $iPayClient->getOrderStatusExtended($data);
$result2 = $iPayClient->getOrderStatusExtended($data2);

if ($result->isSuccess()) {
    echo "Get Order Status initiated successfully. Customer Message: " . $result->getActionCodeDescription();
} else {
    echo "Get Order Status initiation failed: " . $result->getActionCodeDescription();
}
