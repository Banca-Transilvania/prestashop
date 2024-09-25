<?php

namespace BTiPay\Request;

use BTiPay\Helper\SubjectReader;
use BTiPay\Request\BuilderInterface;

class AmountRequestBuilder implements BuilderInterface
{
    public function build(array $buildSubject): array
    {
        $amount = SubjectReader::readAmount($buildSubject);

        return [
            'amount' => $amount * 100
        ];
    }
}