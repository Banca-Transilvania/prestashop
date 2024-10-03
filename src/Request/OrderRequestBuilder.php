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

namespace BTiPay\Request;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Helper\SubjectReader;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderRequestBuilder implements BuilderInterface
{
    public function build(array $buildSubject)
    {
        $orderId = SubjectReader::readOrderId($buildSubject);
        $context = SubjectReader::readContext($buildSubject);
        $saveCard = SubjectReader::readIsSaveCard($buildSubject);
        $secureKey = SubjectReader::readSecureKey($buildSubject);
        $btConfig = SubjectReader::readBTConfiguration($buildSubject);

        $order = new \Order($orderId);
        $customer = new \Customer($context->getCustomerId());

        return [
            'orderNumber' => $this->getOrderId($orderId),
            'amount' => $this->getAmount($order),
            'currency' => $this->getCurrency($order),
            'description' => $this->getDescription($btConfig, $orderId),
            'email' => $customer->email,
            'returnUrl' => $context->getModuleLink(
                'btipay',
                'return',
                [
                    'save_card' => $saveCard,
                    'secureKey' => $secureKey,
                ]
            ),
        ];
    }

    private function getOrderId(int $orderId): string
    {
        return $orderId . '-' . time();
    }

    private function getAmount($order): float
    {
        return round($order->total_paid_tax_incl * 100);
    }

    private function getCurrency($order): string
    {
        $id_currency = $order->id_currency;
        $currency = new \Currency($id_currency);

        return $currency->iso_code_num;
    }

    /**
     * @param BTiPayConfig|null $btConfig
     * @param string|int $orderId
     *
     * @return string
     */
    private function getDescription($btConfig, $orderId): string
    {
        $description = "Comanda nr. $orderId  prin iPay BT la: " . $this->getBaseUrl();
        if ($btConfig instanceof BTiPayConfig) {
            $descriptionTemplate = $btConfig->getDescription();
            if ($descriptionTemplate) {
                $description = str_replace(
                    ['$orderId', '$shopUrl'],
                    [$orderId, $this->getBaseUrl()],
                    $descriptionTemplate
                );
            }
        }

        return $description;
    }

    /**
     * Return base url of the shop
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        return _PS_BASE_URL_ . __PS_BASE_URI__;
    }
}
