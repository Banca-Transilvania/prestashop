<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace BTiPay\Service;

use BTiPay\Command\CommandInterface;
use BTiPay\Config\BTiPayConfig;
use BTiPay\Entity\BTIPayPayment;
use BTiPay\Entity\BTIPayRefund;
use BTiPay\Exception\BTRefundException;
use BTiPay\Repository\PaymentRepository;
use BTransilvania\Api\Model\IPayStatuses;
use BTransilvania\Api\Model\Response\DepositResponse;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CaptureService
{
    protected string $action = 'capture';

    protected ?BTIPayPayment $pay = null;
    protected ?BTIPayPayment $loy = null;

    protected BTiPayConfig $btConfig;
    protected PaymentRepository $paymentRepository;
    protected PaymentDetailsService $paymentDetailsService;
    protected CommandInterface $captureCommand;
    protected LoggerInterface $logger;

    public function __construct(
        BTiPayConfig $btConfig,
        PaymentRepository $paymentRepository,
        PaymentDetailsService $paymentDetailsService,
        CommandInterface $captureCommand,
        LoggerInterface $logger,
    ) {
        $this->btConfig = $btConfig;
        $this->paymentRepository = $paymentRepository;
        $this->paymentDetailsService = $paymentDetailsService;
        $this->captureCommand = $captureCommand;
        $this->logger = $logger;
    }

    /**
     * @throws BTRefundException
     * @throws \Exception
     */
    public function execute($data, $type, $amount = null): void
    {
        if (!$this->btConfig->isEnabled()) {
            $this->logger->info('Capture process or BT iPay is disabled.');

            return;
        }

        /** @var \Order $order */
        $order = $data['order'];
        $payments = $this->paymentRepository->findByOrderId($order->id);

        $this->setPayments($payments);

        $paymentDetails = null;
        $loyDetails = null;

        if ($this->pay) {
            $paymentDetails = $this->paymentDetailsService->get($this->pay->ipay_id);
        }

        $loyId = null;
        if ($this->loy) {
            $loyId = $this->loy->ipay_id;
        }

        if ($paymentDetails) {
            $loyId = $paymentDetails->getLoyId();
        }

        if ($loyId) {
            /** @var GetOrderStatusResponseModel $loyDetails */
            $loyDetails = $this->paymentDetailsService->get($loyId);
        }

        $totalApproved = $this->calculateTotalApproved($paymentDetails, $loyDetails);

        if ($amount <= 0 && $this->action !== 'cancel') {
            $this->logger->info($this->action . ' amount is equal with 0' . $this->pay->currency);
            throw new \Exception($this->action . ' amount is equal with 0' . $this->pay->currency);
        }

        if (!$amount) {
            $amount = $this->getTotalAmount($order, $type);
        }

        $maxCapture = $this->determineAmount($amount, $totalApproved);

        $captureBoth = false;

        if ($loyDetails && $loyDetails->getAmount() > 0) {
            $loyTotalApproved = $loyDetails->getTotalAvailableForCancel();
            if ($loyDetails->isAuthorized() && $loyTotalApproved > 0) {
                $captureBoth = true;
            }

            if ($captureBoth === true) {
                $loyToCapture = $this->determineAmount($maxCapture, $loyTotalApproved);
                $captureSubject = [
                    'ipayId' => $loyId,
                    'amount' => $loyToCapture,
                    'order_id' => $order->id,
                ];

                $data['loy'] = [
                    'order_id' => $order->id,
                    'ipay_id' => $loyId,
                    'amount' => $loyToCapture,
                    'status' => BTIPayRefund::FAILED,
                    'type' => BTIPayRefund::NONE_REFUND,
                    'is_loy' => 1,
                ];

                /** @var DepositResponse $loyCaptureResponse */
                $loyCaptureResponse = $this->captureCommand->execute($captureSubject);

                if ($loyCaptureResponse->isSuccess()) {
                    $maxCapture -= $loyToCapture;
                    $this->setActionAmount($this->loy, $loyToCapture);
                    $this->loy->status = $this->getSuccessBTStatus();
                    $data['loy']['amount'] = $loyToCapture;
                    $data['loy']['status'] = BTIPayRefund::SUCCESS;
                    if ($loyTotalApproved - $loyToCapture > 0.001) {
                        $data['loy']['type'] = BTIPayRefund::PARTIAL_REFUND;
                    } else {
                        $data['loy']['type'] = BTIPayRefund::FULL_REFUND;
                    }
                }

                $this->setPaymentData($this->loy, $data['loy']);
                $this->loy->save();
            }
        }

        if ($paymentDetails && $maxCapture > 0) {
            $amountApproved = $paymentDetails->getTotalAvailableForCancel();
            $amountApproved = $this->determineAmount($maxCapture, $amountApproved);
            $captureSubject = [
                'ipayId' => $this->pay->ipay_id,
                'amount' => $amountApproved,
                'order_id' => $order->id,
            ];

            /** @var DepositResponse $paymentRefundResponse */
            $paymentCaptureResponse = $this->captureCommand->execute($captureSubject);

            $data['pay'] = [
                'order_id' => $order->id,
                'ipay_id' => $this->pay->ipay_id,
                'amount' => $amountApproved,
                'status' => BTIPayRefund::FAILED,
                'type' => BTIPayRefund::NONE_REFUND,
                'is_loy' => 1,
            ];

            if ($paymentCaptureResponse->isSuccess()) {
                $maxCapture -= $amountApproved;
                $this->setActionAmount($this->pay, $amountApproved);
                $this->pay->status = $this->getSuccessBTStatus();
                $data['pay']['amount'] = $amountApproved;
                $data['pay']['status'] = BTIPayRefund::SUCCESS;
                if ($maxCapture > 0.001) {
                    $data['pay']['type'] = BTIPayRefund::PARTIAL_REFUND;
                } else {
                    $data['pay']['type'] = BTIPayRefund::FULL_REFUND;
                }
            }

            $this->setPaymentData($this->pay, $data['pay']);
            $this->pay->save();
        }

        $payStatus = $this->pay->status ?? null;
        $loyStatus = $this->loy->status ?? null;
        $paymentStatus = IPayStatuses::getCombinedStatus($payStatus, $loyStatus);

        if ($paymentStatus && $paymentStatus == $this->getSuccessBTStatus()) {
            $loyCaptured = $this->getActionAmount($this->loy) ?? 0;
            $paymentCaptured = $this->getActionAmount($this->pay) ?? 0;
            $totalAmountCaptured = $loyCaptured + $paymentCaptured;
            $this->createInvoiceAndMarkAsPaid($order, $totalAmountCaptured, $paymentStatus);
        }
    }

    protected function setPaymentData(BTIPayPayment $transaction, array $data)
    {
        $paymentData = \json_decode($transaction->data, true) ?? [];
        $paymentData = array_merge($paymentData, [$this->action => $data]);
        $transaction->data = \json_encode($paymentData, true);
    }

    protected function setActionAmount(BTIPayPayment $transaction, $amount)
    {
        $transaction->capture_amount = $amount;
    }

    protected function getActionAmount(?BTIPayPayment $transaction = null)
    {
        if ($transaction) {
            return $transaction->capture_amount;
        }

        return 0;
    }

    private function setPayments(array $payments)
    {
        foreach ($payments as $pay) {
            if (!$this->pay && $pay->currency !== 'LOY') {
                $this->pay = $pay;
            } elseif (!$this->loy && $pay->currency === 'LOY') {
                $this->loy = $pay;
            }
        }
    }

    private function calculateTotalApproved($paymentDetails, $loyDetails): float
    {
        $totalApproved = 0;
        if ($paymentDetails) {
            $totalApproved = $paymentDetails->getTotalAvailableForCancel();
        }
        if ($loyDetails) {
            $totalApproved += $loyDetails->getTotalAvailableForCancel();
        }

        return $totalApproved;
    }

    /**
     * Get Order Total Amount
     *
     * @param \Order $order
     * @param string $type
     *
     * @return float
     */
    private function getTotalAmount(\Order $order, string $type): float
    {
        $amount = 0;
        if ($type == 'btipay_api_payment_handle') {
            $amount = $order->total_paid;
        }

        return (float) $amount;
    }

    private function determineAmount($amountRequest, float $maxAmountToRefund): float
    {
        if ($amountRequest > $maxAmountToRefund) {
            return $maxAmountToRefund;
        }

        return $amountRequest;
    }

    protected function getSuccessBTStatus()
    {
        return IPayStatuses::STATUS_DEPOSITED;
    }

    protected function getCaptureStatus(string $paymentType, ?string $loyType)
    {
        if (in_array($loyType, [BTIPayRefund::FULL_REFUND, BTIPayRefund::PARTIAL_REFUND])
            && $paymentType == BTIPayRefund::NONE_REFUND) {
            return IPayStatuses::STATUS_DEPOSITED;
        } elseif (in_array($loyType, [BTIPayRefund::FULL_REFUND, BTIPayRefund::PARTIAL_REFUND])
            && $paymentType !== BTIPayRefund::NONE_REFUND) {
            return IPayStatuses::STATUS_APPROVED;
        } elseif ($paymentType == BTIPayRefund::FULL_REFUND || $paymentType == BTIPayRefund::PARTIAL_REFUND) {
            return IPayStatuses::STATUS_DEPOSITED;
        } elseif ($loyType == BTIPayRefund::NONE_REFUND && $paymentType == BTIPayRefund::NONE_REFUND) {
            return false;
        }

        return IPayStatuses::STATUS_APPROVED;
    }

    /**
     * @param \Order $order
     * @param float $totalAmountCaptured
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function createInvoiceAndMarkAsPaid($order, $totalAmountCaptured, $paymentStatus)
    {
        try {
            if (!\Validate::isLoadedObject($order)) {
                throw new \Exception('Order not found.');
            }

            /** @var \OrderPayment $orderPayments */
            $orderPayments = \OrderPayment::getByOrderReference($order->reference);
            if (count($orderPayments) > 0) {
                $orderPayment = array_shift($orderPayments);
            } else {
                throw new \Exception('Payment details not found.');
            }

            $orderPayment->amount = $totalAmountCaptured;

            if (!$orderPayment->save()) {
                throw new \Exception('Failed to save payment information.');
            }

            $this->logger->info('Order and payment marked as paid.');

            // Mark the order as paid and generate invoice,
            // The total_paid_real updated by orderPayment amount classes/order/Order.php:1962 based on $orderPayment->amount
            if ($order && !$order->hasInvoice()) {
                if ($totalAmountCaptured < $order->total_paid) {
                    $orderStatus = $this->getPartialCaptureOrderStatus();
                } else {
                    $orderStatus = $this->getOrderStatusForEntireAmount(); // Set the order status to "Payment accepted"
                }

                if ($order->current_state != $orderStatus) {
                    if ($paymentStatus == IPayStatuses::STATUS_DEPOSITED) {
                        $this->setOrderState($order, $orderStatus, 'Invoice generated programmatically.');
                    }
                }

                if ($order->hasInvoice()) {
                    return true;
                } else {
                    throw new \Exception('Invoice creation failed.');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark order as paid: ' . $e->getMessage());
            throw new \Exception('Error processing order payment status.');
        }

        return false;
    }

    /**
     * Set order state with a message
     *
     * @param \Order $order
     * @param int $stateId
     * @param string $message
     *
     * @throws PrestaShopException
     */
    private function setOrderState($order, $stateId, $message)
    {
        $history = new \OrderHistory();
        $history->id_order = $order->id;
        $history->changeIdOrderState($stateId, $order, true);
        $history->addWithemail(true, [
            'order_name' => $order->getUniqReference(),
            'message' => $message,
        ]);
    }

    /**
     * Get Order status when the transaction is fully captured or canceled
     *
     * @return int
     */
    protected function getOrderStatusForEntireAmount()
    {
        return (int) \Configuration::get('PS_OS_PAYMENT');
    }

    private function getPartialCaptureOrderStatus()
    {
        return $this->btConfig->getPartialCaptureStatus() ?? (int) \Configuration::get('PS_OS_PAYMENT');
    }
}
