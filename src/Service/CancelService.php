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

use BTiPay\Entity\BTIPayPayment;
use BTiPay\Entity\BTIPayRefund;
use BTransilvania\Api\Model\IPayStatuses;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CancelService extends CaptureService
{
    protected string $action = 'cancel';

    protected function getCaptureStatus(string $paymentType, ?string $loyType)
    {
        if (in_array($loyType, [BTIPayRefund::FULL_REFUND, BTIPayRefund::PARTIAL_REFUND])
            && $paymentType == BTIPayRefund::NONE_REFUND) {
            return IPayStatuses::STATUS_REVERSED;
        } elseif ($paymentType == BTIPayRefund::FULL_REFUND || $paymentType == BTIPayRefund::PARTIAL_REFUND) {
            return IPayStatuses::STATUS_REVERSED;
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

            if ($order && !$order->hasInvoice()) {
                $paymentAcceptedStatusId = $this->getOrderStatusForEntireAmount(); // Set the order status to "Payment accepted"
                if ($order->current_state != $paymentAcceptedStatusId) {
                    $order->setCurrentState($paymentAcceptedStatusId);
                    $order->update();

                    $msg = new \Message();
                    $msg->message = 'Cancel Order programmatically.';
                    $msg->id_order = (int) $order->id;
                    $msg->private = 1;
                    $msg->add();
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark order as paid: ' . $e->getMessage());
            throw new \Exception('Error processing order payment status.');
        }

        return false;
    }

    /**
     * Get Order status when the transaction is fully captured or canceled
     *
     * @return int
     */
    protected function getOrderStatusForEntireAmount()
    {
        return (int) \Configuration::get('PS_OS_CANCELED');
    }

    protected function getSuccessBTStatus()
    {
        return IPayStatuses::STATUS_REVERSED;
    }

    protected function setActionAmount(BTIPayPayment $transaction, $amount)
    {
        $transaction->cancel_amount = $amount;
    }

    protected function getActionAmount(?BTIPayPayment $transaction = null)
    {
        if ($transaction) {
            return $transaction->cancel_amount;
        }

        return 0;
    }
}
