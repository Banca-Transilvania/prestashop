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

use BTiPay\Helper\SubjectReader;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderBundleRequestBuilder implements BuilderInterface
{
    private const ADDRESS_MAX_LENGTH = 50;
    private const CITY_MAX_LENGTH = 40;
    private \Order $order;
    private string $carrierName = '';

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function build(array $buildSubject)
    {
        $orderId = SubjectReader::readOrderId($buildSubject);

        $this->order = new \Order($orderId);
        $customer = new \Customer($this->order->id_customer);

        $shippingAddress = new \Address($this->order->id_address_delivery);
        $billingAddress = new \Address($this->order->id_address_invoice);

        return [
            'orderBundle' => [
                'orderCreationDate' => (new \DateTime('now', new \DateTimeZone('Europe/Bucharest')))->format('Y-m-d'),
                'customerDetails' => [
                    'email' => $customer->email,
                    'phone' => $billingAddress->phone,
                    'contact' => $customer->firstname . ' ' . $customer->lastname,
                    'deliveryInfo' => $this->getAddressInfo($shippingAddress),
                    'billingInfo' => $this->getAddressInfo($billingAddress),
                ],
            ],
        ];
    }

    private function getCarrierName(): string
    {
        if (!$this->carrierName) {
            $carrierId = $this->order->id_carrier;
            $carrier = new \Carrier($carrierId);
            $this->carrierName = $carrier->name ?? 'carrier';
        }

        return $this->carrierName;
    }

    private function getAddressChunks(\Address $address): array
    {
        $address1 = $address->address1;
        $remainder = $address->address2;

        $data = [];

        if (strlen($address1) <= self::ADDRESS_MAX_LENGTH) {
            $data['postAddress'] = $address1;
        } else {
            $data['postAddress'] = substr($address1, 0, self::ADDRESS_MAX_LENGTH);
            $remainder = substr($address1, self::ADDRESS_MAX_LENGTH) . $remainder;
        }

        if (!empty($remainder)) {
            if (strlen($remainder) <= self::ADDRESS_MAX_LENGTH) {
                $data['postAddress2'] = trim($remainder);
            } else {
                $data['postAddress2'] = substr($remainder, 0, self::ADDRESS_MAX_LENGTH);
                $data['postAddress3'] = substr(trim(substr($remainder, self::ADDRESS_MAX_LENGTH)), 0, self::ADDRESS_MAX_LENGTH);
            }
        }

        return $data;
    }

    private function getAddressInfo(\Address $address): array
    {
        $address_country = new \Country($address->id_country);
        $data = [
            'deliveryType' => $this->getCarrierName(),
            'country' => $address_country->iso_code,
            'city' => substr($address->city, 0, 40),
            'postalCode' => $address->postcode,
        ];

        return array_merge($data, $this->getAddressChunks($address));
    }

    private function getLastSpace(string $str): int
    {
        $lastSpace = strrpos(substr($str, 0, self::ADDRESS_MAX_LENGTH), ' ');
        if ($lastSpace === false || $lastSpace < self::ADDRESS_MAX_LENGTH - 10) {
            $lastSpace = self::ADDRESS_MAX_LENGTH;
        }

        return $lastSpace;
    }
}
