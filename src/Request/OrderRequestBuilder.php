<?php

namespace BTiPay\Request;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Helper\SubjectReader;
use Currency;
use Order;

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
            'amount'      => $this->getAmount($order),
            'currency'    => $this->getCurrency($order),
            'description' => $this->getDescription($btConfig, $orderId),
            'email'       => $customer->email,
            'returnUrl'   => $context->getModuleLink(
                'btipay',
                'return',
                [
                    'save_card' => $saveCard,
                    'secureKey' => $secureKey
                ]
            )
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
        $currency = new Currency($id_currency);

        return $currency->iso_code_num;
    }

    /**
     * @param BTiPayConfig|null $btConfig
     * @param string|int $orderId
     * @return string
     */
    private function getDescription($btConfig, $orderId): string
    {
        $description = "Comanda nr. $orderId  prin iPay BT la: " . $this->getBaseUrl();
        if ($btConfig instanceof BTiPayConfig) {
            $descriptionTemplate = $btConfig->getDescription();
            if($descriptionTemplate) {
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