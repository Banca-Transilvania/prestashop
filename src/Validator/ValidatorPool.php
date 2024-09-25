<?php

namespace BTiPay\Validator;

use BTiPay\Config\BTiPayConfig;

class ValidatorPool implements ValidatorInterface
{
    private BTiPayConfig $btConfig;
    private array $validators;

    public function __construct(BTiPayConfig $btConfig, array $validators)
    {
        $this->btConfig = $btConfig;
        $this->validators = $validators;
    }

    public function validate($params, $config = null): bool
    {
        $isValid = true;

        foreach ($this->validators as $validator) {
            try {
                $validatorInstance = new $validator();
                if (!$validatorInstance instanceof ValidatorInterface) {
                    throw new \InvalidArgumentException("Validator must implement ValidatorInterface");
                }
                $isValid = $isValid && $validatorInstance->validate($params, $this->btConfig);
            } catch (\Exception $e) {
                $isValid = false;
            }
        }

        return $isValid;
    }
}