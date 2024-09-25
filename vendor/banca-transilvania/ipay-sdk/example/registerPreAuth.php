<?php

require_once 'bootstrap.php';

$data = [
    'orderNumber' => '8042112', // The specific order number from your request.
    'amount'      => '1200', // The amount specified in your request.
    'currency'    => 'RON', // The currency code from your request as per ISO 4217. Ex: RON/946.
    'description' => 'testBT', // Description as provided in your request.
    'returnUrl'   => 'https://magazinulmeu.ro/finish.html', // The returnUrl as specified.
    'orderBundle' => [
        'orderCreationDate' => '2020-09-29', // The date of order creation.
        'customerDetails'   => [
            'email' => 'email@test.com', // Customer email.
            'phone' => '40740123456', // Customer phone number.
            'deliveryInfo' => [
                'deliveryType' => 'comanda', // Delivery type.
                'country'      => 'Romania', // Country code as per ISO 3166-1 name/code/numeric. Ex: Romania/RO/642.
                'city'         => 'Cluj', // City.
                'postAddress'  => 'Str.Sperantei', // Postal address.
                'postalCode'   => '12345', // Postal code.
            ],
            'billingInfo'  => [
                'deliveryType' => 'comanda', // Billing delivery type.
                'country'      => 'RO', // Country code as per ISO 3166-1 name/code/numeric. Ex: Romania/RO/642.
                'city'         => 'Cluj', // City for billing.
                'postAddress'  => 'Str.Sperantei', // First postal address for billing.
                'postAddress2' => 'Str.Speraneti', // Second postal address for billing.
                'postAddress3' => 'Strada', // Third postal address for billing.
                'postalCode'   => '12345', // Postal code for billing.
            ]
        ]
    ]
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

$result = $iPayClient->registerPreAuth($data);

if ($result->isSuccess()) {
    if ($result->hasRedirect()) {
        echo "Redirect customer to the payment page: " . $result->getRedirectURL();
    }
    echo "Payment initiated successfully. Transaction ID: " . $result->getCustomerError();
} else {
    echo "Payment initiation failed: " . $result->getErrorMessage();
}
