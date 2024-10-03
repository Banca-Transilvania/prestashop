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
if (!defined('_PS_VERSION_')) {
    exit;
}

use BTiPay\Facade\Context as BTContext;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BtipayReturnModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        if ($this->module->active == false) {
            exit;
        }

        $orderId = null;
        $secureKey = null;

        try {
            $this->validateReturn();

            $ipayId = Tools::getValue('orderId');
            $token = Tools::getValue('token');
            $saveCard = Tools::getValue('save_card');
            $secureKey = Tools::getValue('secureKey');

            /** @var BTiPay\Repository\PaymentRepository */
            $paymentsRepository = $this->get('btipay.payment_repository');

            /** @var Order $order */
            $order = $paymentsRepository->getOrderByIPayId($ipayId);

            if ($order) {
                $this->validateOrderOwnership($order, $secureKey);
            }

            /** @var GetOrderStatusResponseModel $response */
            $response = $this->executePaymentDetailsCommand($ipayId, $token, $saveCard);
            $orderId = explode('-', $response->orderNumber);
            $orderId = $orderId[0];

            if ($orderId !== $order->id) {
                $order = new Order($orderId);
                $this->validateOrderOwnership($order, $secureKey);
            }

            if ($response->isSuccess() && $response->paymentIsAccepted()) {
                $this->handleSuccess($response, $order);
            } else {
                $this->handleError($response->getCustomerError() ?: 'Payment failed!', $orderId, $secureKey);
            }
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), $orderId, $secureKey);
        }
    }

    private function executePaymentDetailsCommand($ipayId, $token, $saveCard)
    {
        /** @var BTContext $context */
        $context = $this->get('btipay.facade.context');
        $paymentDetailsCommand = $this->get('btipay.payment_details.command');

        return $paymentDetailsCommand->execute([
            'ipayId' => $ipayId,
            'token' => $token,
            'context' => $context,
            'saveCard' => $saveCard,
        ]);
    }

    protected function validateReturn()
    {
        if (!is_string(Tools::getValue('orderId'))) {
            throw new Exception('Invalid return `orderId`', 1);
        }

        if (!is_string(Tools::getValue('token'))) {
            throw new Exception('Invalid return `token`', 1);
        }

        return true;
    }

    private function handleSuccess(GetOrderStatusResponseModel $response, Order $order)
    {
        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $order->secure_key);
    }

    private function handleError($errorMessage, $orderId, $secureKey = null)
    {
        if (empty($errorMessage)) {
            $errorMessage = $this->module->l(
                'Your payment was unsuccessful. Please try again or choose another payment method.'
            );
        }

        $this->context->smarty->assign(
            [
                'order_id' => $orderId,
                'errors' => $errorMessage,
                'payment_link' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    [
                        'orderId' => $orderId,
                        'secureKey' => $secureKey,
                    ],
                    true
                ),
            ]
        );

        $this->setTemplate('module:btipay/views/templates/front/error.tpl');
    }

    private function validateOrderOwnership($order, $secureKey)
    {
        if ($order->id_customer !== $this->context->customer->id) {
            throw new Exception($this->module->l('Order does not belong to the authenticated user.'));
        }

        if ($order->secure_key !== $secureKey) {
            throw new Exception($this->module->l('Invalid secure key.'));
        }
    }
}
