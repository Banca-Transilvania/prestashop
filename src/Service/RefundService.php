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
use BTiPay\Repository\RefundRepository;
use BTransilvania\Api\Model\IPayStatuses;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use BTransilvania\Api\Model\Response\RefundResponse;
use PrestaShop\PrestaShop\Adapter\Order\Refund\OrderRefundSummary;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssuePartialRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssueStandardRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\VoucherRefundType;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RefundService
{
    public static $alreadyRefunded = false;

    protected ?BTIPayPayment $pay = null;
    protected ?BTIPayPayment $loy = null;

    private BTiPayConfig $btConfig;
    private PaymentRepository $paymentRepository;
    private RefundRepository $refundRepository;
    private PaymentDetailsService $paymentDetailsService;
    private CommandInterface $refundCommand;
    private LoggerInterface $logger;
    private ?RefundCommandService $refundCommandService;

    private string $refundType = 'standard';

    public function __construct(
        BTiPayConfig $btConfig,
        PaymentRepository $paymentRepository,
        RefundRepository $refundRepository,
        PaymentDetailsService $paymentDetailsService,
        CommandInterface $refundCommand,
        LoggerInterface $logger,
        ?RefundCommandService $refundCommandService = null,
    ) {
        $this->btConfig = $btConfig;
        $this->paymentRepository = $paymentRepository;
        $this->refundRepository = $refundRepository;
        $this->paymentDetailsService = $paymentDetailsService;
        $this->refundCommand = $refundCommand;
        $this->logger = $logger;
        $this->refundCommandService = $refundCommandService;
    }

    /**
     * Get refund data
     *
     * @param IssueStandardRefundCommand|IssuePartialRefundCommand $command
     *
     * @return OrderRefundSummary
     */
    public function getRefundSummary($command): OrderRefundSummary
    {
        $order = $this->getOrder($command);

        return $this->refundCommandService->getRefundSummary($order, $command);
    }

    /**
     * Get order from command
     *
     * @param IssueStandardRefundCommand|IssuePartialRefundCommand $command
     *
     * @return \Order
     */
    private function getOrder($command): \Order
    {
        return new \Order($command->getOrderId()->getValue());
    }

    /**
     * Refund after creating credit slip
     *
     * @param IssueStandardRefundCommand|IssuePartialRefundCommand $command
     * @param OrderRefundSummary $refundSummary
     *
     * @return void
     */
    public function autoRefund($command, $refundSummary)
    {
        $checkboxBtRequest = \Tools::getValue('cancel_product')['send_refund_request_btipay'] ?? false;
        if ($this->btConfig->isAutoRefundEnabled()
            && self::$alreadyRefunded === false
            && $checkboxBtRequest
        ) {
            $order = $this->getOrder($command);
            $payments = $this->paymentRepository->findByOrderId($order->id);
            $amount = $refundSummary->getRefundedAmount();

            if ($payments) {
                $this->refund($order, $payments, $amount);
            }
        }
    }

    /**
     * Refund after creating credit slip
     *
     * @param IssueStandardRefundCommand|IssuePartialRefundCommand $command
     * @param OrderRefundSummary $refundSummary
     *
     * @return void
     */
    public function customRefund($data, $type, $amount)
    {
        if (!self::$alreadyRefunded) {
            /** @var \Order $order */
            $order = $data['order'];
            $payments = $this->paymentRepository->findByOrderId($order->id);
            $this->refundType = $type;

            if ($payments) {
                $this->refund($order, $payments, $amount);
            }
        }
    }

    /**
     * @throws BTRefundException
     */
    public function refund(\Order $order, $payments, $amount = null): void
    {
        if (!$this->btConfig->isEnabled()) {
            $this->logger->info('Refund process or BT iPay is disabled.');

            return;
        }

        if ($amount <= 0) {
            $this->logger->info('Amount refunded is equal with 0.');
            throw new BTRefundException('Amount refunded is equal with 0.');
        }

        if (!$amount) {
            $amount = $this->getTotalAmountRefund($order);
        }

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

        $totalPaid = $this->calculateTotalPaid($paymentDetails, $loyDetails);
        $amount = round($amount, 2);

        $maxRefunded = $this->determineAmount($amount, $totalPaid);

        $refundLoy = false;
        if (($paymentDetails && $paymentDetails->getLoyAmount() > 0)
            || ($loyDetails && $loyDetails->getAmount() > 0)) {
            if ($loyDetails && $loyDetails->canRefund() && $loyDetails->getTotalAvailableForRefund() > 0) {
                $refundLoy = true;
            }
        }

        if ($refundLoy === true) {
            $loyToRefund = $this->determineAmount($maxRefunded, $loyDetails->getTotalAvailableForRefund());
            $refundSubject = [
                'ipayId' => $loyId,
                'amount' => $loyToRefund,
                'order_id' => $order->id,
            ];

            /** @var RefundResponse $loyRefundResponse */
            $loyRefundResponse = $this->refundCommand->execute($refundSubject);

            $loyiPayRefund = new BTIPayRefund();
            $loyiPayRefund->order_id = $order->id;
            $loyiPayRefund->return_id = time();
            $loyiPayRefund->ipay_id = $loyId;
            $loyiPayRefund->amount = 0;
            $loyiPayRefund->status = 'Failed';
            $loyiPayRefund->type = BTIPayRefund::NONE_REFUND;
            $loyiPayRefund->currency = 'LOY';

            if ($loyRefundResponse->isSuccess()) {
                $maxRefunded -= $loyToRefund;
                $this->loy->refund_amount = $loyDetails->getTotalRefunded() + $loyToRefund;
                $loyiPayRefund->amount = $loyToRefund;
                $loyiPayRefund->status = 'Success';
                if ($loyDetails->getTotalAvailableForRefund() - $loyToRefund > 0.001) {
                    $loyiPayRefund->type = BTIPayRefund::PARTIAL_REFUND;
                    $this->loy->status = IPayStatuses::STATUS_PARTIALLY_REFUNDED;
                } else {
                    $loyiPayRefund->type = BTIPayRefund::FULL_REFUND;
                    $this->loy->status = IPayStatuses::STATUS_REFUNDED;
                }
            }

            $this->loy->save();
            $loyiPayRefund->save();
        }

        if ($maxRefunded > 0 && $paymentDetails) {
            $refundSubject = [
                'ipayId' => $this->pay->ipay_id,
                'amount' => $maxRefunded,
            ];
            /** @var RefundResponse $paymentRefundResponse */
            $paymentRefundResponse = $this->refundCommand->execute($refundSubject);

            $paymentiPayRefund = new BTIPayRefund();
            $paymentiPayRefund->order_id = $order->id;
            $paymentiPayRefund->return_id = time();
            $paymentiPayRefund->ipay_id = $this->pay->ipay_id;
            $paymentiPayRefund->amount = 0;
            $paymentiPayRefund->status = 'Failed';
            $paymentiPayRefund->type = BTIPayRefund::NONE_REFUND;
            $currency = new \Currency($order->id_currency);
            $paymentiPayRefund->currency = $currency->iso_code;

            if ($paymentRefundResponse->isSuccess()) {
                $this->pay->refund_amount = $paymentDetails->getTotalRefunded() + $maxRefunded;
                $paymentiPayRefund->amount = $maxRefunded;
                $paymentiPayRefund->status = 'Success';
                if ($paymentDetails->getTotalAvailableForRefund() - $maxRefunded > 0.001) {
                    $paymentiPayRefund->type = BTIPayRefund::PARTIAL_REFUND;
                    $this->pay->status = IPayStatuses::STATUS_PARTIALLY_REFUNDED;
                } else {
                    $paymentiPayRefund->type = BTIPayRefund::FULL_REFUND;
                    $this->pay->status = IPayStatuses::STATUS_REFUNDED;
                }
            }

            $this->pay->save();
            $paymentiPayRefund->save();
        }

        $loyStatus = $this->pay->status ?? null;
        $payStatus = $this->loy->status ?? null;
        $paymentStatus = IPayStatuses::getCombinedStatus($payStatus, $loyStatus);

        if ($paymentStatus) {
            if ($paymentStatus === IPayStatuses::STATUS_PARTIALLY_REFUNDED) {
                $orderStatusAdmin = $this->getOrderStatusForPartialAmount();

                $order->setCurrentState($orderStatusAdmin);
                $order->update();

                //                $loyRefund = $loyiPayRefund->amount ?? 0;
                //                $payRefund = $paymentiPayRefund->amount ?? 0;
                //                $this->createGenericCreditSlip($order, $loyRefund + $payRefund);
            }

            if ($paymentStatus === IPayStatuses::STATUS_REFUNDED) {
                $orderStatusAdmin = $this->getOrderStatusForEntireAmount();

                if ($this->refundType !== 'status_changed') {
                    $order->setCurrentState($orderStatusAdmin);
                    $order->update();
                }

                if ($this->btConfig->createOrderSlipOnFullRefund() && !self::$alreadyRefunded) {
                    $orderDetailList = $order->getOrderDetailList();
                    $fullRefund = [];

                    foreach ($orderDetailList as $orderDetail) {
                        $fullRefund[$orderDetail['id_order_detail']] = [
                            'quantity' => (int) $orderDetail['product_quantity'] - (int) $orderDetail['product_quantity_refunded'],
                        ];
                    }

                    self::$alreadyRefunded = true;

                    $refundDetails['refunds'] = $fullRefund;
                    $refundDetails['refundShippingCost'] = true;
                    $refundDetails['generateCreditSlip'] = true;
                    $refundDetails['generateVoucher'] = false;
                    $refundDetails['voucherRefundType'] = VoucherRefundType::PRODUCT_PRICES_EXCLUDING_VOUCHER_REFUND;

                    $this->refundCommandService->processRefund($order, $refundDetails, $paymentStatus);
                }
            }
        }
    }

    public function createGenericCreditSlip($order, $totalRefundAmount, $refundShipping = false, $taxRate = null)
    {
        if (!\Validate::isLoadedObject($order)) {
            return 'Order cannot be loaded';
        }

        if ($taxRate === null) {
            $taxRate = 0.19;
        }

        $orderSlip = new \OrderSlip();
        $orderSlip->id_customer = $order->id_customer;
        $orderSlip->id_order = $order->id;
        $orderSlip->conversion_rate = $order->conversion_rate;
        $orderSlip->partial = 1;

        $orderSlip->total_products_tax_excl = $totalRefundAmount / (1 + $taxRate);
        $orderSlip->total_products_tax_incl = $totalRefundAmount;
        $orderSlip->total_shipping_tax_incl = 0;
        $orderSlip->total_shipping_tax_excl = 0;

        if ($refundShipping && $order->total_shipping_tax_incl > 0) {
            $orderSlip->total_shipping_tax_incl = min($order->total_shipping_tax_incl, $totalRefundAmount);
            $orderSlip->total_shipping_tax_excl = $orderSlip->total_shipping_tax_incl / (1 + $order->carrier_tax_rate / 100);
        }

        if ($orderSlip->add()) {
            return 'Credit slip created successfully';
        } else {
            return 'Failed to create credit slip';
        }
    }

    /**
     * Get Order status when the transaction is fully refunded
     *
     * @return int
     */
    protected function getOrderStatusForEntireAmount()
    {
        return (int) \Configuration::get('PS_OS_REFUND');
    }

    /**
     * Get Order status when the transaction is partial refunded
     *
     * @return int
     */
    protected function getOrderStatusForPartialAmount()
    {
        return (int) \Configuration::get(BTiPayConfig::BTIPAY_STATUS_PARTIAL_REFUND);
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

    private function calculateTotalPaid(?GetOrderStatusResponseModel $paymentDetails, ?GetOrderStatusResponseModel $loyDetails): float
    {
        $totalApproved = 0;
        if ($paymentDetails) {
            $totalApproved = $paymentDetails->getTotalAvailableForRefund();
        }
        if ($loyDetails) {
            $totalApproved += $loyDetails->getTotalAvailableForRefund();
        }

        return $totalApproved;
    }

    /**
     * @param \Order $order
     *
     * @return float
     */
    private function getTotalAmountRefund(\Order $order): float
    {
        return $order->getTotalPaid();
    }

    private function floatValue($val): float
    {
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/\.(?=.*\.)/', '', $val);

        return (float) $val;
    }

    private function validateAmount($amount, float $totalPaid): void
    {
        if ($totalPaid === 0.0) {
            throw new BTRefundException('Cannot process refund, no available amount found for refund');
        }

        if ($amount - $totalPaid > 0.001) {
            throw new BTRefundException('Cannot process refund, a maximum of ' . $totalPaid . ' can be refunded');
        }
    }

    private function determineAmount($amountRequest, float $maxAmountToRefund): float
    {
        if ($amountRequest > $maxAmountToRefund) {
            return $maxAmountToRefund;
        }

        return $amountRequest;
    }

    private function getRefundStatus(string $paymentType, ?string $loyType)
    {
        if ($loyType == BTIPayRefund::FULL_REFUND && $paymentType == BTIPayRefund::NONE_REFUND) {
            return IPayStatuses::STATUS_REFUNDED;
        } elseif (($loyType == BTIPayRefund::FULL_REFUND || $loyType == BTIPayRefund::NONE_REFUND)
            && $paymentType == BTIPayRefund::FULL_REFUND) {
            return IPayStatuses::STATUS_REFUNDED;
        } elseif ($loyType == BTIPayRefund::NONE_REFUND && $paymentType == BTIPayRefund::NONE_REFUND) {
            return false;
        }

        return IPayStatuses::STATUS_PARTIALLY_REFUNDED;
    }
}
