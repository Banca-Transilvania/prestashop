<?php

require_once 'bootstrap.php';

$data = [
    // Unique order number on the payment page - from the order registration API response
    'mdOrder'   => 'cb02cbbb-5a81-426a-92f2-e78f5cafdfff',
    // The unique identifier of the saved card.
    'bindingId' => '22b963cf-cb2b-4ca0-9ca8-a3630492543e'
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

$result = $iPayClient->paymentOrderBinding($data);

if ($result->isSuccess()) {
    echo "Payment Order Binding successfully. Customer Message: " . $result->getErrorMessage();
} else {
    echo "Payment Order Binding initiation failed: " . $result->getErrorMessage();
}
