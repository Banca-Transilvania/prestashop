<?php

namespace BTiPay\Validator;

interface ValidatorInterface
{
    public function validate($params, $config = null): bool;
}