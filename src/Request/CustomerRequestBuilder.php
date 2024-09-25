<?php

namespace BTiPay\Request;

use BTiPay\Helper\SubjectReader;
use BTiPay\Request\BuilderInterface;
use Order;

class CustomerRequestBuilder implements BuilderInterface
{
    public function build(array $buildSubject)
    {
        $orderId = SubjectReader::readOrderId($buildSubject);
        $saveCard = SubjectReader::readIsSaveCard($buildSubject);
        $cardOnFileEnabled = SubjectReader::readIsCardOnFileEnabled($buildSubject);

        $order = new Order($orderId);
        $customerId = $order->id_customer;

        if ($customerId !== 0 && $cardOnFileEnabled && $saveCard) {
            return ['clientId' => $customerId];
        }

        return [];
    }
}