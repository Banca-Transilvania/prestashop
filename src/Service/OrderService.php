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

use BTiPay\Config\BTiPayConfig;
use BTiPay\Repository\PaymentRepository;
use BTransilvania\Api\Model\IPayStatuses;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderService
{
    private LoggerInterface $logger;
    private PaymentRepository $paymentRepository;

    public function __construct(
        PaymentRepository $paymentRepository,
        LoggerInterface $logger
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->logger = $logger;
    }

    public function updateOrderStatus($order)
    {
        if (is_scalar($order)) {
            $order = new \Order($order);
        }

        if (!\Validate::isLoadedObject($order)) {
            throw new \Exception('Order not found.');
        }

        $this->paymentRepository->setPaymentsByOrderId($order->id);

        $payStatus = $this->paymentRepository->getPayTransaction()->status ?? null;
        $loyStatus = $this->paymentRepository->getLoyTransaction()->status ?? null;

        $paymentStatus = IPayStatuses::getCombinedStatus($payStatus, $loyStatus);
        $orderStatus = $order->current_state;

        $newOrderStatus = $this->mapStatusToOrderState($paymentStatus);

        if ($newOrderStatus != $orderStatus) {
            if ($newOrderStatus == $this->mapStatusToOrderState(IPayStatuses::STATUS_DEPOSITED)) {
                $loyCaptured = $this->paymentRepository->getLoyTransaction()->capture_amount ?? 0;
                $paymentCaptured = $this->paymentRepository->getPayTransaction()->capture_amount ?? 0;
                $totalAmountCaptured = $loyCaptured + $paymentCaptured;
                $this->createInvoiceAndMarkAsPaid($order, $totalAmountCaptured, $newOrderStatus);
            } else {
                $order->setCurrentState($newOrderStatus);
                $order->update();
            }
        }
    }

    public function mapStatusToOrderState($status): int
    {
        $statusMap = [
            IPayStatuses::STATUS_CREATED => (int) \Configuration::get(BTiPayConfig::BTIPAY_STATUS_AWAITING),
            IPayStatuses::STATUS_PENDING => (int) \Configuration::get(BTiPayConfig::BTIPAY_STATUS_AWAITING),
            IPayStatuses::STATUS_DECLINED => (int) \Configuration::get('PS_OS_ERROR'),
            IPayStatuses::STATUS_APPROVED => (int) \Configuration::get(BTiPayConfig::BTIPAY_STATUS_APPROVED),
            IPayStatuses::STATUS_REVERSED => (int) \Configuration::get('PS_OS_CANCELED'),
            IPayStatuses::STATUS_DEPOSITED => (int) \Configuration::get('PS_OS_PAYMENT'),
            IPayStatuses::STATUS_PARTIALLY_REFUNDED => (int) \Configuration::get(BTiPayConfig::BTIPAY_STATUS_PARTIAL_REFUND),
            IPayStatuses::STATUS_REFUNDED => (int) \Configuration::get('PS_OS_REFUND'),
        ];

        return $statusMap[$status] ?? (int) \Configuration::get('PS_OS_ERROR');
    }

    /**
     * Set order state with a message
     *
     * @param \Order $order
     * @param int $stateId
     * @param string $message
     *
     * @throws \PrestaShopException
     */
    public function setOrderState($order, $stateId, $message)
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
     * @param \Order $order
     * @param float $totalAmountCaptured
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function createInvoiceAndMarkAsPaid($order, $totalAmountCaptured, $newOrderStatus)
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

            if ($order && !$order->hasInvoice()) {
                $this->setOrderState($order, $newOrderStatus, 'Invoice generated programmatically.');

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
}
