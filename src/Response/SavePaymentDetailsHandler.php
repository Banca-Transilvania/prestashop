<?php

namespace BTiPay\Response;

use BTiPay\Entity\BTIPayPayment;
use BTiPay\Exception\CommandException;
use BTiPay\Helper\SubjectReader;
use BTiPay\Repository\PaymentRepository;
use BTiPay\Response\HandlerInterface;
use BTiPay\Service\PaymentDetailsService;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

class SavePaymentDetailsHandler implements HandlerInterface
{
    private PaymentRepository $paymentRepository;
    private PaymentDetailsService $paymentDetailsService;

    public function __construct(
        PaymentRepository $paymentRepository,
        PaymentDetailsService $paymentDetailsService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentDetailsService = $paymentDetailsService;
    }

    /**
     * Handles the saving of transaction data from a payment gateway response.
     *
     * @param array $handlingSubject Subject containing the order information.
     * @param GetOrderStatusResponseModel $response Response from the payment gateway.
     * @return void
     * @throws CommandException
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $iPayId = SubjectReader::readIPayId($handlingSubject);
        $payment = $this->paymentRepository->findByIPayId($iPayId);

        if (!$payment) {
            $payment = new BTIPayPayment();
            $payment->payment_tries = 0;
        }

        $this->paymentRepository->updatePaymentFromResponse($payment, $response, $iPayId, $iPayId);

        $loyId = $response->getLoyId() ?? null;

        if($loyId) {
            /** @var GetOrderStatusResponseModel $loyDetails */
            $loyDetails = $this->paymentDetailsService->get($loyId);

            $loyTransaction = $this->paymentRepository->findByIPayId($loyId);

            if (!$loyTransaction) {
                $loyTransaction = clone $payment;
                $loyTransaction->id = null;
            }

            $this->paymentRepository->updatePaymentFromResponse($loyTransaction, $loyDetails, $loyId, $iPayId);
        }

        if (!$this->paymentRepository->save($payment)) {
            throw new CommandException("Payment Transaction can not be saved in database");
        }

        if ($loyId && !$this->paymentRepository->save($loyTransaction)) {
            throw new CommandException("Loyalty Transaction can not be saved in database");
        }
    }
}