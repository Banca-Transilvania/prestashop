<?php

namespace BTiPay\Client;

interface ClientInterface
{
    /**
     * Send Request using PHP SDK
     *
     * @param string $action
     * @param array $transferObject
     * @return mixed
     */
    public function placeRequest(string $action, array $transferObject);

    /**
     * Get Payment Details
     *
     * @param array $transferObject
     * @return mixed
     */
    public function getPaymentDetails(array $transferObject);

    /**
     * Enable Card Status
     *
     * @param array $transferObject
     * @return mixed
     */
    public function bindCard(array $transferObject);

    /**
     * Disable Card Status
     *
     * @param array $transferObject
     * @return mixed
     */
    public function unBindCard(array $transferObject);
}