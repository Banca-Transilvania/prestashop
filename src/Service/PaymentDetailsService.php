<?php

namespace BTiPay\Service;

use BTiPay\Client\ClientInterface;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;

class PaymentDetailsService
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $iPayId
     * @return GetOrderStatusResponseModel
     */
    public function get(string $iPayId)
    {
        return $this->client->getPaymentDetails(['orderId' => $iPayId]);
    }
}