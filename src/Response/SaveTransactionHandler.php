<?php

namespace BTiPay\Response;

use BTiPay\Entity\BTIPayPayment;
use BTiPay\Exception\CommandException;
use BTiPay\Helper\SubjectReader;
use BTiPay\Repository\PaymentRepository;
use BTransilvania\Api\Model\IPayStatuses;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

class SaveTransactionHandler implements HandlerInterface
{
    private $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Handles the saving of transaction data from a payment gateway response.
     *
     * @param array $handlingSubject Subject containing the order information.
     * @param ResponseModelInterface $response Response from the payment gateway.
     * @return void
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $orderId = SubjectReader::readOrderId($handlingSubject);
        $payments = $this->paymentRepository->findByOrderId($orderId);

        if(!$payments) {
            $payment = new BTIPayPayment();
            $payment->payment_tries = 0;
        } else {
            $payment = array_shift($payments);
        }

        $payment->order_id = $orderId;
        $payment->ipay_id = $response->getOrderId();
        $payment->parent_ipay_id = $response->getOrderId();
        $payment->ipay_url = $response->getRedirectUrl();
        $payment->payment_tries += 1;
        $payment->status = IPayStatuses::STATUS_PENDING;

        if (!$this->paymentRepository->save($payment)) {
            throw new CommandException("Transaction can not be saved in database");
        }
    }
}