<?php

namespace BTiPay\Validator\Availability;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Validator\ValidatorInterface;
use Currency;

class CurrencyValidator implements ValidatorInterface
{

    public function validate($params, $config = null): bool
    {
        if (!$this->checkConfigCurrency($params['cart'], $config)) {
            return false;
        }

        return true;
    }

    private function checkConfigCurrency($cart, BTiPayConfig $config): bool
    {
        $currency_order = new Currency($cart->id_currency);
        $allowedCurrencies = $config->getAllowedCurrencies();
        if (is_array($allowedCurrencies)) {
            if (in_array($currency_order->id, $allowedCurrencies)) {
                return true;
            }
        }

        return false;
    }

}