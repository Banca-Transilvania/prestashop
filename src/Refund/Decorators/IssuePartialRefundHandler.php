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

namespace BTiPay\Refund\Decorators;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Service\RefundService;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssuePartialRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\IssueStandardRefundCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\CommandHandler\IssuePartialRefundHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\InvalidOrderStateException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class IssuePartialRefundHandler implements IssuePartialRefundHandlerInterface
{
    /**
     * @var IssuePartialRefundHandlerInterface
     */
    protected $handler;

    /**
     * @var RefundService
     */
    protected $refundService;

    public function __construct(
        IssuePartialRefundHandlerInterface $handler,
        RefundService $refundService,
    ) {
        $this->handler = $handler;
        $this->refundService = $refundService;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(IssuePartialRefundCommand $command): void
    {
        $order = $this->getOrder($command);

        if (in_array($order->current_state, [(int) \Configuration::get(BTiPayConfig::BTIPAY_STATUS_APPROVED), (int) \Configuration::get('PS_OS_CANCELED')])) {
            throw new InvalidOrderStateException(InvalidOrderStateException::NOT_PAID, 'You can not perform a refund, invalid payment state');
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
