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

namespace BTiPay\Response;

use BTiPay\Entity\BTIPayPayment;
use BTiPay\Exception\CommandException;
use BTiPay\Helper\SubjectReader;
use BTiPay\Repository\PaymentRepository;
use BTransilvania\Api\Model\IPayStatuses;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SaveTransactionHandler implements HandlerInterface
{
    private $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Handles the saving of transaction data from a payment gateway response.
     *
     * @param array $handlingSubject subject containing the order information
     * @param ResponseModelInterface $response response from the payment gateway
     *
     * @return void
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $orderId = SubjectReader::readOrderId($handlingSubject);
        $payments = $this->paymentRepository->findByOrderId($orderId);

        if (!$payments) {
            $payment = new BTIPayPayment();
            $payment->payment_tries = 0;
        } else {
            $payment = array_shift($payments);
        }

        $payment->order_id = $orderId;
        $payment->ipay_id = $response->getOrderId();
        $payment->parent_ipay_id = $response->getOrderId();
        $payment->ipay_url = $response->getRedirectUrl();
        ++$payment->payment_tries;
        $payment->status = IPayStatuses::STATUS_PENDING;

        if (!$this->paymentRepository->save($payment)) {
            throw new CommandException('Transaction can not be saved in database');
        }
    }
}
