<?php

namespace BTiPay\Response;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Entity\BTIPayPayment;
use BTiPay\Exception\CommandException;
use BTiPay\Helper\SubjectReader;
use BTiPay\Repository\PaymentRepository;
use BTransilvania\Api\Model\IPayStatuses;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use BTransilvania\Api\Model\Response\ResponseModelInterface;
use PrestaShopException;

class UpdateStatusHandler implements HandlerInterface
{
    private PaymentRepository $paymentRepository;
    private BTiPayConfig $config;

    public function __construct(PaymentRepository $paymentRepository, BTiPayConfig $config)
    {
        $this->paymentRepository = $paymentRepository;
        $this->config = $config;
    }

    /**
     * @param array $handlingSubject
     * @param GetOrderStatusResponseModel $response
     * @return void
     * @throws CommandException
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $iPayId = SubjectReader::readIPayId($handlingSubject);

        /** @var BTIPayPayment $payment */
        $payment = $this->paymentRepository->findByIPayId($iPayId);

        /** @var \Order $order */
        $order = $this->paymentRepository->getOrderByPayment($payment);

        if (!$payment || !$order) {
            throw new CommandException('Payment or Order not found.');
        }

        $this->updateOrderStatus($response, $order, $payment);
    }

    /**
     * @throws PrestaShopException
     * @throws CommandException
     */
    private function updateOrderStatus($response, $order, $payment): void
    {
        $errorCode = $response->getErrorCode();
        $status = $response->getStatus();
        $message = $response->getActionCodeDescription();

        if ($errorCode != 0) {
            $this->handleError($order, $payment, $message);
            return;
        }

        try {
            if ($status == IPayStatuses::STATUS_APPROVED) {
                $orderStatus = $this->config->getApproveOrderStatus();
            } else {
                $orderStatus = $this->mapStatusToOrderState($status);
            }
            if ($orderStatus !== $order->getCurrentState()) {
                $this->setOrderState($order, $orderStatus, $message);
            }
            $payment->status = $status;
            $this->paymentRepository->save($payment);
        } catch (\Exception $e) {
            $payment->payment_tries += 1;
            $this->paymentRepository->save($payment);
            throw new CommandException('Failed to update the order status: ' . $e->getMessage());
        }
    }

    /**
     * Handle payment error
     *
     * @param \Order $order
     * @param BTIPayPayment $payment
     * @param string $message
     * @throws PrestaShopException
     */
    private function handleError($order, $payment, $message): void
    {
        $payment->payment_tries += 1;
        $this->setOrderState($order, (int)\Configuration::get('PS_OS_ERROR'), "Mesaj de eroare: $message");
    }

    /**
     * Set order state with a message
     *
     * @param \Order $order
     * @param int $stateId
     * @param string $message
     * @throws PrestaShopException
     */
    private function setOrderState($order, $stateId, $message)
    {
        $history = new \OrderHistory();
        $history->id_order = $order->id;
        $history->changeIdOrderState($stateId, $order, true);
        $history->addWithemail(true, [
            'order_name' => $order->getUniqReference(),
            'message'    => $message
        ]);
    }

    private function mapStatusToOrderState($status): int
    {
        $statusMap = [
            IPayStatuses::STATUS_DEPOSITED => (int) \Configuration::get('PS_OS_PAYMENT'),
            IPayStatuses::STATUS_REVERSED  => (int) \Configuration::get('PS_OS_CANCELED'),
            IPayStatuses::STATUS_REFUNDED  => (int) \Configuration::get('PS_OS_REFUND'),
            IPayStatuses::STATUS_DECLINED  => (int) \Configuration::get('PS_OS_ERROR'),
        ];

        return $statusMap[$status] ?? 8; // Default to Payment error if status not mapped
    }
}