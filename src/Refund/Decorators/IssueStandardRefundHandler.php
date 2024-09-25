<?php

namespace BTiPay\Refund\Decorators;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Service\RefundService;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssuePartialRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssueStandardRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\CommandHandler\IssuePartialRefundHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\CommandHandler\IssueStandardRefundHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidOrderStateException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class IssueStandardRefundHandler implements IssueStandardRefundHandlerInterface
{
    /**
     * @var IssueStandardRefundHandlerInterface
     */
    protected $handler;

    /**
     * @var RefundService
     */
    protected $refundService;

    public function __construct(
        IssueStandardRefundHandlerInterface $handler,
        RefundService $refundService
    ) {
        $this->handler = $handler;
        $this->refundService = $refundService;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(IssueStandardRefundCommand $command): void
    {
        $order = $this->getOrder($command);

        if(in_array($order->current_state ,[(int)\Configuration::get(BTiPayConfig::BTIPAY_STATUS_APPROVED), (int) \Configuration::get('PS_OS_CANCELED')])) {
            throw new InvalidOrderStateException(
                InvalidOrderStateException::NOT_PAID,
                'You can not perform a refund, invalid payment state'
            );
        }

        $refundSummary = $this->refundService->getRefundSummary($command);
        $this->handler->handle($command);
        $this->refundService->autoRefund($command, $refundSummary);
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
}