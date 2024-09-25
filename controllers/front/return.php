<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use BTiPay\Facade\Context as BTContext;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class BtipayReturnModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        if ($this->module->active == false) {
            die;
        }

        $orderId = null;
        $secureKey = null;

        try {
            $this->validateReturn();

            $ipayId = Tools::getValue('orderId');
            $token = Tools::getValue('token');
            $saveCard = Tools::getValue('save_card');
            $secureKey = Tools::getValue('secureKey');

            /** @var \BTiPay\Repository\PaymentRepository */
            $paymentsRepository = $this->get('btipay.payment_repository');

            /** @var \Order $order */
            $order = $paymentsRepository->getOrderByIPayId($ipayId);

            if($order) {
                $this->validateOrderOwnership($order, $secureKey);
            }

            /** @var GetOrderStatusResponseModel $response */
            $response = $this->executePaymentDetailsCommand($ipayId, $token, $saveCard);
            $orderId = explode("-", $response->orderNumber);
            $orderId = $orderId[0];

            if($orderId !== $order->id) {
                $order = new Order($orderId);
                $this->validateOrderOwnership($order, $secureKey);
            }

            if ($response->isSuccess() && $response->paymentIsAccepted()) {
                $this->handleSuccess($response, $order);
            } else {
                $this->handleError($response->getCustomerError() ?: 'Payment failed!', $orderId, $secureKey);
            }
        } catch (\Exception $e) {
            $this->handleError($e->getMessage(), $orderId, $secureKey);
        }
    }

    private function executePaymentDetailsCommand($ipayId, $token, $saveCard)
    {
        /** @var BTContext $context */
        $context = $this->get('btipay.facade.context');
        $paymentDetailsCommand = $this->get('btipay.payment_details.command');

        return $paymentDetailsCommand->execute([
            'ipayId'   => $ipayId,
            'token'    => $token,
            'context'  => $context,
            'saveCard' => $saveCard
        ]);
    }

    protected function validateReturn()
    {
        if (!is_string(Tools::getValue('orderId'))) {
            throw new \Exception('Invalid return `orderId`', 1);
        }

        if (!is_string(Tools::getValue('token'))) {
            throw new \Exception('Invalid return `token`', 1);
        }

        return true;
    }

    private function handleSuccess(GetOrderStatusResponseModel $response, \Order $order)
    {
        \Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $order->id . '&key=' . $order->secure_key);
    }

    private function handleError($errorMessage, $orderId, $secureKey = null)
    {
        if(empty($errorMessage)) {
            $errorMessage = $this->module->l(
                'Your payment was unsuccessful. Please try again or choose another payment method.'
            );
        }

        $this->context->smarty->assign(
            [
                'order_id'     => $orderId,
                'errors'       => $errorMessage,
                'payment_link' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    [
                        'orderId'   => $orderId,
                        'secureKey' => $secureKey
                    ],
                    true
                )
            ]
        );

        $this->setTemplate('module:btipay/views/templates/front/error.tpl');
    }

    private function validateOrderOwnership($order, $secureKey)
    {
        if ($order->id_customer !== $this->context->customer->id) {
            throw new \Exception($this->module->l('Order does not belong to the authenticated user.'));
        }

        if ($order->secure_key !== $secureKey) {
            throw new \Exception($this->module->l('Invalid secure key.'));
        }
    }
}
