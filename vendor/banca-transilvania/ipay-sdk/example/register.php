<?php

require_once 'bootstrap.php';

$data = [
    'orderNumber' => '209126', // The specific order number for this transaction.
    'amount'      => '1000', // The transaction amount in the smallest currency unit (e.g., cents for USD/EUR).
    'currency'    => 'RON', // The code/numeric currency code as per ISO 4217. Ex: RON/946
    'description' => 'testBT', // A description of the transaction for reference.
    'returnUrl'   => 'https://magazinulmeu.ro/finish.html', // The URL to which the customer will be redirected.
    'orderBundle' => [
        'orderCreationDate' => '2020-09-29', // The date when the order was created.
        'customerDetails'   => [
            'email' => 'email@test.com', // The customer's email address.
            'phone' => '40740123456', // The customer's phone number.
            'deliveryInfo' => [
                'deliveryType' => 'comanda', // Type of the order delivery/billing
                'country'      => 'Romania', // The country code as per ISO 3166-1 name/code/numeric. Ex: Romania/RO/642
                'city'         => 'Cluj', // The city of the delivery address.
                'postAddress'  => 'Str.Sperantei', // The street address for delivery.
                'postalCode'   => '12345' // The postal code for the delivery address.
            ],
            'billingInfo'  => [
                'deliveryType' => 'comanda', // The type of billing delivery (e.g., standard, express).
                'country'      => '642', // The country code as per ISO 3166-1 name/code/numeric. Ex: Romania/RO/642
                'city'         => 'Cluj', // The city of the billing address.
                'postAddress'  => 'Str.Sperantei', // The first line of the billing address.
                'postAddress2' => 'Str.Speraneti', // The second line of the billing address (optional).
                'postAddress3' => 'Strada', // The third line of the billing address (optional).
                'postalCode'   => '12345' // The postal code for the billing address.
            ]
        ]
    ]
];

$iPayClient = new \BTransilvania\Api\IPayClient(
    [
        'user'         => '*****', // Replace '*****' with your actual iPay user.
        'password'     => '*****', // Replace '*****' with your actual iPay password.
        'environment'  => 'test', // The environment of the API ('test' or 'production').
        'platformName' => 'Magento - Community', // The platform name for integration, e.g., Magento.
        'language'     => 'en' // The language setting for the API interaction.
    ]
);

try {
    $result = $iPayClient->register($data);
} catch (\BTransilvania\Api\Exception\ApiException $exception) {
    echo "Payment initiation failed: " . $exception->getPlainMessage();
    exit;
}

if ($result->isSuccess()) {
    if ($result->hasRedirect()) {
        echo "Redirect customer to the payment page: " . $result->getRedirectURL();
    }
    echo "Payment initiated successfully. Transaction ID: " . $result->getOrderId();
} else {
    echo "Payment initiation failed: " . $result->getErrorMessage();
}
