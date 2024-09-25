<?php

namespace BTiPay\Response;

use BTiPay\Response\HandlerInterface;
use BTransilvania\Api\Model\Response\RefundResponse;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

class RefundPaymentHandler implements HandlerInterface
{
    /**
     * Handles the saving of cards data from a payment gateway response.
     *
     * @param array $handlingSubject Subject containing the order information.
     * @param RefundResponse $response Response from the payment gateway.
     * @return void
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {

    }
}