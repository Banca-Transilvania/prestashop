# Banca Transilvania iPay SDK

This SDK facilitates communication between payment modules and the iPay API, offering a streamlined integration for handling payments through Banca Transilvania's iPay system.

## Features

- Easy integration with the iPay API.
- Support for multiple payment methods.
- Secure handling of payment transactions.
- Comprehensive error handling and logging.

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- OpenSSL extension

## Installation

Install the package via Composer:

```bash
composer require banca-transilvania/ipay-sdk
```

## Usage

Here's a simple example of how to use the SDK:

```php
use BTransilvania\Api\IPayClient;

$this->iPayClient = new \BTransilvania\Api\IPayClient(
    [
        'user'         => $user,
        'password'     => $password,
        'environment'  => $environment,
        'platformName' => $platformName,
        'language'     => $language
    ]
);

// Initiate a payment
$result = $this->iPayClient->register($data);

if ($result->isSuccessful()) {
    echo "Payment initiated successfully. Transaction ID: " . $result->getTransactionId();
} else {
    echo "Payment initiation failed: " . $result->getErrorMessage();
}
```

## Configuration

You can configure the SDK by passing configuration parameters to the `PaymentProcessor` constructor. Here are some of the configuration options available:

- `apiKey`: Your iPay API key.
- `environment`: Set to `sandbox` for testing or `production` for live payments.
- More configurations...

## Contributing

Contributions are welcome, and any help is greatly appreciated! See the `CONTRIBUTING.md` file for how to get started.

## Support

For support, please contact [contact@bancatransilvania.com](mailto:contact@bancatransilvania.com) or visit [BTePOS website](https://btepos.ro/contact).

## License

This project is licensed under the Apache License 2.0 - see the `LICENSE` file for details.
```