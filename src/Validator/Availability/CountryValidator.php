<?php

namespace BTiPay\Validator\Availability;

use Address;
use BTiPay\Config\BTiPayConfig;
use BTiPay\Validator\ValidatorInterface;

class CountryValidator implements ValidatorInterface
{

    public function validate($params, $config = null): bool
    {
        if (!$this->checkConfigCountry($params['cart'], $config)) {
            return false;
        }

        return true;
    }

    /**
     * @param $cart
     * @param BTiPayConfig $config
     * @return bool
     */
    private function checkConfigCountry($cart, BTiPayConfig $config): bool
    {
        if ($config->isAllCountriesEnabled()) {
            return true;
        }

        $billingAddress = new Address($cart->id_address_invoice);
        $countryId = $billingAddress->id_country;

        $specificCountries = $config->getSpecificCountries();
        if (is_array($specificCountries)) {
            if (in_array($countryId, $specificCountries)) {
                return true;
            }
        }

        return false;
    }
}