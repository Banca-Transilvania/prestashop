<?php

namespace BTiPay\Response;

use BTransilvania\Api\Model\Response\ResponseModelInterface;

interface HandlerInterface
{
    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param ResponseModelInterface $response
     * @return void
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void;
}