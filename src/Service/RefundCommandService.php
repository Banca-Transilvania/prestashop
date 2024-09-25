<?php

namespace BTiPay\Service;

use BTransilvania\Api\Model\IPayStatuses;
use PrestaShop\Decimal\DecimalNumber;
use PrestaShop\PrestaShop\Adapter\Order\Refund\OrderRefundCalculator;
use PrestaShop\PrestaShop\Adapter\Order\Refund\OrderRefundSummary;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssuePartialRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssueStandardRefundCommand;
use Psr\Log\LoggerInterface;

class RefundCommandService
{
    private $commandBus;
    private $logger;
    private $refundCalculator; // Added for calculating refund summary

    public function __construct(
        CommandBusInterface $commandBus,
        LoggerInterface $logger,
        OrderRefundCalculator $refundCalculator // Newly added dependency
    ) {
        $this->commandBus = $commandBus;
        $this->logger = $logger;
        $this->refundCalculator = $refundCalculator; // Set the refund calculator
    }

    /**
     * Calculate and return the refund summary based on the given command.
     *
     * @param \Order $order
     * @param mixed $command Either IssueStandardRefundCommand or IssuePartialRefundCommand
     * @return OrderRefundSummary
     */
    public function getRefundSummary(\Order $order, $command): OrderRefundSummary
    {
        if ($command instanceof IssuePartialRefundCommand) {
            $shippingRefundAmount = $command->getShippingCostRefundAmount();
        } else {
            $shippingRefundAmount = new DecimalNumber((string) ($command->refundShippingCost() ? $order->total_shipping_tax_incl : 0));
        }

        return $this->refundCalculator->computeOrderRefund(
            $order,
            $command->getOrderDetailRefunds(),
            $shippingRefundAmount,
            $command->getVoucherRefundType(),
            $command->getVoucherRefundAmount()
        );
    }

    public function processRefund(\Order $order, array $refundDetails, $orderStatus)
    {
        if ($orderStatus === IPayStatuses::STATUS_PARTIALLY_REFUNDED) {
            $this->issuePartialRefund($order, $refundDetails);
        } elseif ($orderStatus === IPayStatuses::STATUS_REFUNDED) {
            $this->issueStandardRefund($order, $refundDetails);
        }
    }

    private function issueStandardRefund(\Order $order, array $refundDetails)
    {
        try {
            $command = new IssueStandardRefundCommand(
                $order->id,
                $refundDetails['refunds'],
                $refundDetails['refundShippingCost'],
                $refundDetails['generateCreditSlip'],
                $refundDetails['generateVoucher'],
                $refundDetails['voucherRefundType']
            );
            $this->commandBus->handle($command);
        } catch (\Exception $e) {
            $this->logger->error('Error processing standard refund: ' . $e->getMessage());
        }
    }

    private function issuePartialRefund(\Order $order, array $refundDetails)
    {
        try {
            $command = new IssuePartialRefundCommand(
                $order->id,
                $refundDetails['refunds'],
                $refundDetails['shippingCostRefundAmount'],
                $refundDetails['restockRefundedProducts'],
                $refundDetails['generateCreditSlip'],
                $refundDetails['generateVoucher'],
                $refundDetails['voucherRefundType']
            );
            $this->commandBus->handle($command);
        } catch (\Exception $e) {
            $this->logger->error('Error processing partial refund: ' . $e->getMessage());

        }
    }
}
