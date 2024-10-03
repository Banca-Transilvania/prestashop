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

namespace BTiPay\Facade;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Configuration
{
    /** @var Context */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param string $key
     */
    public function get($key, $idShop = null, $idLang = null, $idShopGroup = null): ?string
    {
        if (!$idShop) {
            $idShop = $this->context->getShopId();
        }

        if (!$idShopGroup) {
            $idShopGroup = $this->context->getShopGroupId();
        }

        $result = \Configuration::get($key, $idLang, $idShopGroup, $idShop);

        return !empty($result) ? $result : null;
    }

    /**
     * @param string $key
     */
    public function updateValue($key, $value, $idShop = null, $html = false, $idShopGroup = null): void
    {
        if (!$idShop) {
            $idShop = $this->context->getShopId();
        }

        if (!$idShopGroup) {
            $idShopGroup = $this->context->getShopGroupId();
        }

        \Configuration::updateValue($key, $value, $html, $idShopGroup, $idShop);
    }

    /**
     * @param string $key
     */
    public function delete($key): void
    {
        \Configuration::deleteByName($key);
    }
}
