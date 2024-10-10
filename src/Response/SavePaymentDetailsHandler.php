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
use BTiPay\Service\PaymentDetailsService;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SavePaymentDetailsHandler implements HandlerInterface
{
    private PaymentRepository $paymentRepository;
    private PaymentDetailsService $paymentDetailsService;

    public function __construct(
        PaymentRepository $paymentRepository,
        PaymentDetailsService $paymentDetailsService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentDetailsService = $paymentDetailsService;
    }

    /**
     * Handles the saving of transaction data from a payment gateway response.
     *
     * @param array $handlingSubject subject containing the order information
     * @param GetOrderStatusResponseModel $response response from the payment gateway
     *
     * @return void
     *
     * @throws CommandException
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $iPayId = SubjectReader::readIPayId($handlingSubject);
        $payment = $this->paymentRepository->findByIPayId($iPayId);

        if (!$payment) {
            $payment = new BTIPayPayment();
            $payment->payment_tries = 0;
        }

        $this->paymentRepository->updatePaymentFromResponse($payment, $response, $iPayId, $iPayId);

        $loyId = $response->getLoyId() ?? null;

        if ($loyId) {
            /** @var GetOrderStatusResponseModel $loyDetails */
            $loyDetails = $this->paymentDetailsService->get($loyId);

            $loyTransaction = $this->paymentRepository->findByIPayId($loyId);

            if (!$loyTransaction) {
                $loyTransaction = clone $payment;
                $loyTransaction->id = null;
            }

            $this->paymentRepository->updatePaymentFromResponse($loyTransaction, $loyDetails, $loyId, $iPayId);
        }

        if (!$this->paymentRepository->save($payment)) {
            throw new CommandException('Payment Transaction can not be saved in database');
        }

        if ($loyId && !$this->paymentRepository->save($loyTransaction)) {
            throw new CommandException('Loyalty Transaction can not be saved in database');
        }
    }
}
