<?php

namespace BTiPay\Client;

use BTiPay\Client\ClientInterface;
use BTiPay\Config\BTiPayConfig;
use BTiPay\Facade\Context;
use BTransilvania\Api\IPayClient;
use BTransilvania\Api\Config\Config as SdkConfig;
use BTransilvania\Api\Logger\PsrLogger;
use Psr\Log\LoggerInterface;

class BtpayClient implements ClientInterface
{
    private BTiPayConfig $btConfig;
    private Context $context;
    private ?IPayClient $sdkClient = null;
    private LoggerInterface $logger;

    public function __construct(BTiPayConfig $btConfig, Context $context, LoggerInterface $logger)
    {
        $this->btConfig = $btConfig;
        $this->context = $context;
        $this->logger = $logger;
    }

    /** @inheritDoc */
    public function placeRequest(string $action, array $transferObject)
    {
        return $this->$action($transferObject);
    }

    /** @inheritDoc */
    public function getPaymentDetails($transferObject)
    {
        return $this->createClient()->getOrderStatusExtended($transferObject);
    }

    /** @inheritDoc */
    public function bindCard(array $transferObject)
    {
        return $this->createClient()->bindCard($transferObject);
    }

    /** @inheritDoc */
    public function unBindCard(array $transferObject)
    {
       return $this->createClient()->unBindCard($transferObject);
    }

    private function createClient()
    {
        if (!$this->sdkClient) {
            if($this->btConfig->isTestMode()) {
                $user = $this->btConfig->getTestUsername();
                $password = $this->btConfig->getTestPassword();
                $env = SdkConfig::TEST_MODE;
            } else {
                $user = $this->btConfig->getLiveUsername();
                $password = $this->btConfig->getLivePassword();
                $env = SdkConfig::PROD_MODE;
            }

            $sdk_config = [
                'user'         => $user,
                'password'     => $password,
                'environment'  => $env,
                'platformName' => 'PrestaShop ' . _PS_VERSION_,
                'language'     => $this->context->getLanguageIso()
            ];

            try {
                $sdkLogger = new PsrLogger($this->logger);
                $this->sdkClient = new IPayClient($sdk_config, null, $sdkLogger);
            } catch (\Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                throw $throwable;
            }
        }

        return $this->sdkClient;
    }

    private function order($transferObject)
    {
        return $this->createClient()->register($transferObject);
    }

    private function authorize($transferObject)
    {
        return $this->createClient()->registerPreAuth($transferObject);
    }

    private function refund($transferObject)
    {
        return $this->createClient()->refund($transferObject);
    }

    private function capture($transferObject)
    {
        return $this->createClient()->deposit($transferObject);
    }

    private function cancel($transferObject)
    {
        return $this->createClient()->reverse($transferObject);
    }
}