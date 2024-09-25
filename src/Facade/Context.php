<?php
/**
 * 2007-2024 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2024 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

namespace BTiPay\Facade;

use Cart;
use Configuration;
use Context as BaseContext;
use Tools;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Context
{
    private BaseContext $context;

    public function __construct()
    {
        $this->context = BaseContext::getContext();
    }

    public function getCustomerId(): int
    {
        /* @phpstan-ignore-next-line */
        if (!$this->context->customer) {
            return 0;
        }

        return (int) $this->context->customer->id;
    }

    public function getOrderTotal(): float
    {
        return Tools::ps_round($this->context->cart->getOrderTotal(true, Cart::BOTH), 2);
    }

    public function getCartId(): int
    {
        return (int)$this->context->cart->id;
    }

    public function getCurrencyId(): int
    {
        return (int)$this->context->cart->id_currency;
    }

    public function getModuleLink(
        $module,
        $controller = 'default',
        array $params = [],
        $ssl = null,
        $idLang = null,
        $idShop = null,
        $relativeProtocol = false
    ): string {
        return (string) $this->context->link->getModuleLink(
            $module,
            $controller,
            $params,
            $ssl,
            $idLang,
            $idShop,
            $relativeProtocol
        );
    }

    public function isMobileDevice(): bool
    {
        return (bool)$this->context->isMobile();
    }

    public function getLanguageIso(): string
    {
        return $this->context->language !== null ? (string)$this->context->language->iso_code : 'en';
    }

    public function smartyAssign(array $data)
    {
        $this->context->smarty->assign($data);
    }

    public function getCurrencyIso(): string
    {
        /* @phpstan-ignore-next-line */
        if (!$this->context->currency) {
            return '';
        }

        return (string) $this->context->currency->iso_code;
    }

    /**
     * Translates the given message.
     *
     * @param string $id The message id (may also be an object that can be cast to string)
     * @param array $parameters An array of parameters for the message
     * @param string|null $domain The domain for the message or null to use the default
     * @param string|null $locale The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws InvalidArgumentException If the locale contains invalid characters
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->context->getTranslator()->trans($id, $parameters, $domain, $locale);
    }

















    
    public function getLanguageId(): int
    {
        return (int) $this->context->language->id;
    }





    public function getShopDomain(): string
    {
        return (string) $this->context->shop->domain;
    }

    public function getAdminLink($controllerName, array $params = []): string
    {
        return (string) $this->context->link->getAdminLink($controllerName, true, [], $params);
    }

    public function getCartProducts(): array
    {
        return $this->context->cart->getProducts();
    }

    public function getComputingPrecision(): int
    {
        if (method_exists($this->context, 'getComputingPrecision')) {
            return $this->context->getComputingPrecision();
        }

        return (int) Configuration::get('PS_PRICE_DISPLAY_PRECISION');
    }

    public function getProductLink($product): string
    {
        return (string) $this->context->link->getProductLink($product);
    }

    public function getImageLink($name, $ids, $type = null): string
    {
        return (string) $this->context->link->getImageLink($name, $ids, $type);
    }

    public function getShopId(): int
    {
        return (int) $this->context->shop->id;
    }

    public function getCustomerAddressInvoiceId(): int
    {
        return (int) $this->context->cart->id_address_invoice;
    }



    public function getInvoiceAddressId(): int
    {
        return (int) $this->context->cart->id_address_invoice;
    }

    public function getLanguageLocale(): string
    {
        return (string) $this->context->language->locale;
    }

    public function getCountryId(): int
    {
        return (int) $this->context->country->id;
    }

    public function getShopGroupId(): int
    {
        return (int) $this->context->shop->id_shop_group;
    }

    public function formatPrice(float $price, string $isoCode): string
    {
        $locale = $this->context->getCurrentLocale();

        /* @phpstan-ignore-next-line */
        if (!$locale) {
            return (string) $price;
        }

        return $locale->formatPrice(
            $price,
            $isoCode
        );
    }
}
