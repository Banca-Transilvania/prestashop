<?php

namespace BTiPay\Webhook;

use BTiPay\Entity\BTIPayPayment;
use BTiPay\Entity\BTIPayRefund;
use BTiPay\Repository\PaymentRepository;
use BTiPay\Repository\RefundRepository;
use BTiPay\Service\CaptureService;
use BTiPay\Service\OrderService;
use BTiPay\Service\PaymentDetailsService;
use BTiPay\Service\RefundService;
use BTransilvania\Api\Model\IPayStatuses;

class WebhookService
{
    private const ORDER_STATUS_ACCEPTED = 2;
    private const ORDER_STATUS_CANCELED = 6;
    private const ORDER_STATUS_REFUNDED = 7;
    private const ORDER_STATUS_ERROR    = 8;

    private BTPayJwt $jwtDecoder;
    private \Monolog\Logger $logger;
    private \BTiPay\Config\BTiPayConfig $config;
    private PaymentRepository $paymentRepository;
    private RefundRepository $refundRepository;
    private PaymentDetailsService $paymentDetailsService;
    private OrderService $orderService;

    private \StdClass $payload;

    public function __construct(
        \BTiPay\Webhook\BTPayJwt $jwtDecoder,
        \Monolog\Logger $logger,
        \BTiPay\Config\BTiPayConfig $config,
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository,
        PaymentDetailsService $paymentDetailsService,
        OrderService $orderService
    ) {
        $this->jwtDecoder = $jwtDecoder;
        $this->logger = $logger;
        $this->config = $config;
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
        $this->paymentDetailsService = $paymentDetailsService;
        $this->orderService = $orderService;
    }

    public function executeWebhook(\StdClass $payload)
    {
        $this->payload = $payload;

        $paymentEngineId = $this->getPaymentEngineId();
        if ($paymentEngineId === null) {
            throw new \Exception('Cannot find payment id');
        }

        $paymentStatus = $this->getPaymentStatus();
        if ($paymentStatus === null) {
            throw new \Exception('Cannot find payment status');
        }

        /** @var BTIPayPayment $paymentData */
        $paymentData = $this->getPaymentData();

        if ($paymentData === null) {
            throw new \Exception('Cannot not find payment data in the database');
        }

        $orderId = $this->getOrderId($paymentData);
        if ($orderId === null) {
            throw new \Exception('Cannot find order');
        }

        if ($paymentStatus === IPayStatuses::STATUS_DEPOSITED && !$this->hasFailed()) {
            $this->capture($paymentData);
        }

        if ($paymentStatus === IPayStatuses::STATUS_REVERSED && !$this->hasFailed()) {
            $this->reverse($paymentData);
        }

        if (($paymentStatus === IPayStatuses::STATUS_REFUNDED || $paymentStatus === IPayStatuses::STATUS_PARTIALLY_REFUNDED)
            && !$this->hasFailed()) {
            $this->refund($paymentData, $orderId);
        }

        $this->orderService->updateOrderStatus($orderId);

        return true;
    }

    private function capture(BTIPayPayment $paymentData)
    {
        $paymentDetails = $this->paymentDetailsService->get($paymentData->ipay_id);

        $totalCaptured = $paymentDetails->getTotalDepositedAmount();

        if ($totalCaptured > 0) {
            $paymentData->status = IPayStatuses::STATUS_DEPOSITED;
            $paymentData->capture_amount = $totalCaptured;
        }

        $this->paymentRepository->save($paymentData);
    }

    private function reverse(BTIPayPayment $paymentData)
    {
        $totalCaptured = $paymentData->amount;

        if ($totalCaptured > 0) {
            $paymentData->status = IPayStatuses::STATUS_REVERSED;
            $paymentData->cancel_amount = $totalCaptured;
        }

        $this->paymentRepository->save($paymentData);
    }

    private function refund(BTIPayPayment $paymentData, $orderId)
    {
        $paymentDetails = $this->paymentDetailsService->get($paymentData->ipay_id);

        $totalRefunded = $paymentDetails->getTotalRefunded();
        $status = $paymentDetails->getStatus();

        if ($totalRefunded > 0) {
            $paymentData->status = $status;
            $paymentData->refund_amount = $totalRefunded;

            $order = null;
            if (is_scalar($orderId)) {
                $order = new \Order($orderId);
            }

            if (!\Validate::isLoadedObject($order)) {
                throw new \Exception("Order not found.");
            }

            $alreadyRefundedAmount = $this->refundRepository->getTotalRefundedAmountByIpayId($paymentData->ipay_id);
            $refundAmount = $totalRefunded - $alreadyRefundedAmount;

            if($refundAmount > 0.001)
            {
                $paymentiPayRefund = new BTIPayRefund();
                $paymentiPayRefund->order_id = $order->id;
                $paymentiPayRefund->return_id = time();
                $paymentiPayRefund->ipay_id = $paymentData->ipay_id;
                $paymentiPayRefund->amount = $refundAmount;
                $paymentiPayRefund->status = BTIPayRefund::SUCCESS;
                if($status == IPayStatuses::STATUS_PARTIALLY_REFUNDED) {
                    $paymentiPayRefund->type = BTIPayRefund::PARTIAL_REFUND;
                } else {
                    $paymentiPayRefund->type = BTIPayRefund::FULL_REFUND;
                }
                $paymentiPayRefund->currency = $paymentData->currency;

                $paymentiPayRefund->save();
            }
        }

        $this->paymentRepository->save($paymentData);
    }


    private function addRefund(string $paymentId, \Order $orderService): bool
    {
        $paymentDetails = $this->paymentDetailsService->get($paymentId);

        $paymentTotalRefund = $paymentDetails->getTotalRefunded();
        $availableAmount = $paymentDetails->getTotalAvailableForRefund();

        $refundAmount = $paymentTotalRefund - $this->getRefundedAmount($orderService);
        if ($refundAmount > 0) {
            $data['order'] = $orderService;
            $this->refundService->execute($data, 'webhook', $refundAmount);
            $orderStatus = $this->orderService->mapStatusToOrderState(IPayStatuses::STATUS_REFUNDED);
            $message = sprintf('Successfully refunded amount %s', $refundAmount);
            $this->setOrderState($orderService, $orderStatus, $message);
        }

        return abs($availableAmount) - $refundAmount < 0.001;
    }

    private function getPaymentEngineId(): ?string
    {
        if (property_exists($this->payload, 'mdOrder') &&
            is_string($this->payload->mdOrder)
        ) {
            return $this->payload->mdOrder;
        }
        return null;
    }

    private function hasFailed(): bool
    {
        return property_exists($this->payload, 'status') && is_scalar($this->payload->status) && (int) $this->payload->status !== 1;
    }

    private function getPaymentStatus(): ?string
    {
        if (property_exists($this->payload, 'operation') && is_string($this->payload->operation)) {
            return strtoupper($this->payload->operation);
        }
        return null;
    }

    private function getOrderId(BTIPayPayment $paymentData): ?int
    {
        if (property_exists($paymentData, 'order_id') &&
            is_scalar($paymentData->order_id)
        ) {
            return (int)$paymentData->order_id;
        }
        return null;
    }

    private function getPaymentData(): ?BTIPayPayment
    {
        return $this->paymentRepository->findByIPayId($this->getPaymentEngineId());
    }

    private function getPaymentDataByLoy(): ?BTIPayPayment
    {
        return $this->paymentRepository->findByLoyId($this->getPaymentEngineId());
    }

    /**
     * Get the total amount refunded for a given order.
     *
     * @param \Order $order The ID of the order.
     * @return float The total amount refunded.
     */
    public function getRefundedAmount(\Order $order): float
    {
        if (!\Validate::isLoadedObject($order)) {
            throw new \Exception('Invalid Order ID');
        }

        $totalRefunded = 0.0;

        // Loop through each order detail to calculate the total refunded amount
        foreach ($order->getOrderDetailList() as $orderDetailData) {
            $orderDetail = new \OrderDetail($orderDetailData['id_order_detail']);

            // Calculate the refunded amount for this order detail
            $quantityRefunded = $orderDetail->product_quantity_refunded;
            $amountRefunded = $orderDetail->total_refunded_tax_incl;

            $totalRefunded += $quantityRefunded * $orderDetail->unit_price_tax_incl;
            $totalRefunded += $amountRefunded;
        }

        return $totalRefunded;
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


}