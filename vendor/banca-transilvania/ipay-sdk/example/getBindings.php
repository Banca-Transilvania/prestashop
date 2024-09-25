<?php

require_once 'bootstrap.php';

$data = [
    // Unique customer identifier in the merchant's system.
    'clientId'   => 'cb02cbbb-5a81-426a-92f2-e78f5cafdfff',
    // Also show expired cards. Value: true/false.
    'showExpired' => true
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

$result = $iPayClient->getBindings($data);

if ($result->isSuccess()) {
    echo "Get Saved Cards: " . $result->getSavedCards();
} else {
    echo "Get Saved Cards initiation failed: " . $result->getErrorMessage();
}
