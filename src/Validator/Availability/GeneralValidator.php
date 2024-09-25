<?php

namespace BTiPay\Validator\Availability;

use BTiPay\Validator\ValidatorInterface;

class GeneralValidator implements ValidatorInterface
{
    public function validate($params, $config = null): bool
    {
        if (empty($config) || !$config->isEnabled()) {
            return false;
        }

        if ($config->isTestMode()) {
            if (empty($config->getTestUsername())
                || empty($config->getTestPassword())) {
                return false;
            }
        } else {
            if (empty($config->getLiveUsername())
                || empty($config->getLivePassword())) {
                return false;
            }
        }

        return true;
    }
}