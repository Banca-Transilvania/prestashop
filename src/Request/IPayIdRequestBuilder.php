<?php

namespace BTiPay\Request;

use BTiPay\Helper\SubjectReader;
use BTiPay\Request\BuilderInterface;

class IPayIdRequestBuilder implements BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $orderId = SubjectReader::readIPayId($buildSubject);

        return [
            'orderId' => $orderId
        ];
    }
}