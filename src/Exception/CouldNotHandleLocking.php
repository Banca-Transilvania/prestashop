<?php

namespace BTiPay\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CouldNotHandleLocking extends BTPayException
{
    public static function lockExists(): self
    {
        return new self('Lock exists');
    }

    public static function lockOnAcquireIsMissing(): self
    {
        return new self('Lock on acquire is missing');
    }

    public static function lockOnReleaseIsMissing(): self
    {
        return new self('Lock on release is missing');
    }
}