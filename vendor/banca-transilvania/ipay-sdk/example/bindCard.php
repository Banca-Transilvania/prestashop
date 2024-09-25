<?php

require_once 'bootstrap.php';

$data = [
    // The unique identifier of the saved card.
    'bindingId'   => 'cb02cbbb-5a81-426a-92f2-e78f5cafdfff'
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

$result = $iPayClient->bindCard($data);

if ($result->isSuccess()) {
    echo "Reactivation of saved card:" . $result->getErrorMessage();
} else {
    echo "Reactivation of saved card initiation failed: " . $result->getErrorMessage();
}
