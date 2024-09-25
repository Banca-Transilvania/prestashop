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

$result = $iPayClient->unBindCard($data);

if ($result->isSuccess()) {
    echo "Disable Saved Cards: " . $result->getErrorMessage();
} else {
    echo "Disable initiation failed: " . $result->getErrorMessage();
}
