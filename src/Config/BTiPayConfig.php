<?php

namespace BTiPay\Config;

use BTiPay\Facade\Configuration;
use BTiPay\Facade\Context;
use BTiPay\Helper\Encrypt;

class BTiPayConfig
{
    public const ENABLED              = 'BTIPAY_ENABLED';
    public const TITLE                = 'BTIPAY_TITLE';
    public const DESCRIPTION          = 'BTIPAY_DESCRIPTION';
    public const TEST_MODE            = 'BTIPAY_TEST_MODE';
    public const LIVE_USERNAME        = 'BTIPAY_LIVE_USERNAME';
    public const LIVE_PASSWORD        = 'BTIPAY_LIVE_PASSWORD';
    public const LIVE_SUB_MERCHANT_ID = 'BTIPAY_LIVE_SUB_MERCHANT_ID';
    public const TEST_USERNAME        = 'BTIPAY_TEST_USERNAME';
    public const TEST_PASSWORD        = 'BTIPAY_TEST_PASSWORD';
    public const TEST_SUB_MERCHANT_ID = 'BTIPAY_TEST_SUB_MERCHANT_ID';
    public const CALLBACK_URL         = 'BTIPAY_CALLBACK_URL';
    public const CALLBACK_KEY         = 'BTIPAY_CALLBACK_KEY';

    public const PHASE                 = 'BTIPAY_PHASE';
    public const NEW_ORDER_STATUS      = 'BTIPAY_NEW_ORDER_STATUS';
    public const APPROVED_ORDER_STATUS = 'BTIPAY_APPROVED_ORDER_STATUS';
    public const PARTIAL_CAPTURE_ORDER_STATUS = 'BTIPAY_PARTIAL_CAPTURE_ORDER_STATUS';

    public const ALL_COUNTRIES      = 'BTIPAY_ALL_COUNTRIES';
    public const SPECIFIC_COUNTRIES = 'BTIPAY_SPECIFIC_COUNTRIES';
    public const ALLOWED_CURRENCIES = 'BTIPAY_ALLOWED_CURRENCIES';

    public const GEN_INVOICE          = 'BTIPAY_GEN_INVOICE';
    public const CARD_ON_FILE         = 'BTIPAY_CARD_ON_FILE';
    public const LOGGING              = 'BTIPAY_LOGGING';

    public const AUTO_REFUND          = 'BTIPAY_AUTO_REFUND';
    public const CUSTOM_REFUND_BUTTON = 'BTIPAY_CUSTOM_REFUND_BUTTON';
    public const REFUND_ON_STATUS_CHANGE          = 'BTIPAY_REFUND_ON_STATUS_CHANGE';
    public const CREATE_ORDER_SLIP_ON_FULL_REFUND = 'BTIPAY_CREATE_ORDER_SLIP_ON_FULL_REFUND';

    public const ONE_PHASE = 1;
    public const TWO_PHASE = 2;

    public const BTIPAY_STATUS_AWAITING        = 'BTIPAY_STATUS_AWAITING';
    public const BTIPAY_STATUS_APPROVED        = 'BTIPAY_STATUS_APPROVED';
    public const BTIPAY_STATUS_PARTIAL_REFUND  = 'BTIPAY_STATUS_PARTIAL_REFUND';
    public const BTIPAY_STATUS_PARTIAL_CAPTURE = 'BTIPAY_STATUS_PARTIAL_CAPTURE';

    public const LOCK_TIME_TO_LIVE = 60;

    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration = null)
    {
        if(!$configuration) {
            $configuration = new Configuration(new Context());
        }
        $this->configuration = $configuration;
    }

    public function isEnabled()
    {
        return (bool) $this->configuration->get(self::ENABLED);
    }

    public function getTitle()
    {
        return (string) $this->configuration->get(self::TITLE);
    }

    public function getDescription(): string
    {
        return (string) $this->configuration->get(self::DESCRIPTION);
    }

    public function isTestMode()
    {
        return (bool) $this->configuration->get(self::TEST_MODE);
    }

    public function getLiveUsername()
    {
        return Encrypt::decryptConfigValue($this->configuration->get(self::LIVE_USERNAME));
    }

    public function getLivePassword()
    {
        return Encrypt::decryptConfigValue($this->configuration->get(self::LIVE_PASSWORD));
    }

    public function getLiveSubMerchantId()
    {
        return (string) $this->configuration->get(self::LIVE_SUB_MERCHANT_ID);
    }

    public function getTestUsername()
    {
        return Encrypt::decryptConfigValue($this->configuration->get(self::TEST_USERNAME));
    }

    public function getTestPassword()
    {
        return Encrypt::decryptConfigValue($this->configuration->get(self::TEST_PASSWORD));
    }

    public function getTestSubMerchantId()
    {
        return (string) $this->configuration->get(self::TEST_SUB_MERCHANT_ID);
    }

    public function getPhase()
    {
        return (string) $this->configuration->get(self::PHASE);
    }

    public function getNewOrderStatus()
    {
        return (int)$this->configuration->get(self::NEW_ORDER_STATUS);
    }

    public function getApproveOrderStatus()
    {
        return (int)$this->configuration->get(self::APPROVED_ORDER_STATUS);
    }

    public function getPartialCaptureStatus()
    {
        return (int)$this->configuration->get(self::PARTIAL_CAPTURE_ORDER_STATUS);
    }

    public function isAllCountriesEnabled()
    {
        return (bool)$this->configuration->get(self::ALL_COUNTRIES);
    }

    public function getSpecificCountries()
    {
        return explode(',', $this->configuration->get(self::SPECIFIC_COUNTRIES));
    }

    public function getAllowedCurrencies()
    {
        return explode(',', $this->configuration->get(self::ALLOWED_CURRENCIES)) ;
    }

    public function isInvoiceGenerationEnabled()
    {
        return (bool) $this->configuration->get(self::GEN_INVOICE);
    }

    public function isCardOnFileEnabled()
    {
        return (bool) $this->configuration->get(self::CARD_ON_FILE);
    }

    public function isLoggingEnabled()
    {
        return (bool) $this->configuration->get(self::LOGGING);
    }

    public function isAutoRefundEnabled()
    {
        return (bool) $this->configuration->get(self::AUTO_REFUND);
    }

    public function isCustomRefundEnabled()
    {
        return (bool) $this->configuration->get(self::CUSTOM_REFUND_BUTTON);
    }

    public function isRefundOnStatusChangeEnabled()
    {
        return (bool) $this->configuration->get(self::REFUND_ON_STATUS_CHANGE);
    }

    public function createOrderSlipOnFullRefund()
    {
        return (bool) $this->configuration->get(self::CREATE_ORDER_SLIP_ON_FULL_REFUND);
    }

    public function getCallbackUrl()
    {
        return (string) $this->configuration->get(self::CALLBACK_URL);
    }

    public function getCallbackKey()
    {
        return Encrypt::decryptConfigValue($this->configuration->get(self::CALLBACK_KEY));
    }
}