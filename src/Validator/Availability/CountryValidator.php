<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace BTiPay\Validator\Availability;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Validator\ValidatorInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     *
     * @return bool
     */
    private function checkConfigCountry($cart, BTiPayConfig $config): bool
    {
        if ($config->isAllCountriesEnabled()) {
            return true;
        }

        $billingAddress = new \Address($cart->id_address_invoice);
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
