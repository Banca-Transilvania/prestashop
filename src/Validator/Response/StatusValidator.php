<?php

namespace BTiPay\Validator\Response;

use BTiPay\Helper\SubjectReader;
use BTiPay\Validator\ValidatorInterface;

class StatusValidator implements ValidatorInterface
{
    public function validate($params, $config = null): bool
    {
        $response = SubjectReader::readResponse($params);

        return $response->isSuccess();
    }
}