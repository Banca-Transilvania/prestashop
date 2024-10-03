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

use BTiPay\Config\BTiPayConfig;
use BTransilvania\Api\Model\Response\RegisterResponseModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This Controller simulate an external payment gateway
 */
class BtipayPaymentModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        /** @var BTiPay\Facade\Context $context */
        $context = $this->get('btipay.facade.context');
        /** @var BTiPayConfig $btConfig */
        $btConfig = $this->get('btipay.config');
        /** @var Monolog\Logger $btLogger */
        $btLogger = $this->get('btipay.logger');

        if (!Tools::getIsset('orderId')) {
            if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
                Tools::redirect($this->context->link->getPageLink(
                    'order',
                    true,
                    (int) $this->context->language->id,
                    [
                        'step' => 1,
                    ]
                ));
            }

            $customer = new Customer($this->context->cart->id_customer);

            if (false === Validate::isLoadedObject($customer)) {
                Tools::redirect($this->context->link->getPageLink(
                    'order',
                    true,
                    (int) $this->context->language->id,
                    [
                        'step' => 1,
                    ]
                ));
            }

            $paymentStatus = $btConfig->getNewOrderStatus();
            $total = $context->getOrderTotal();
            $customer = new Customer($context->getCustomerId());
            $secureKey = $customer->secure_key;

            $this->module->validateOrder(
                $context->getCartId(),
                $paymentStatus,
                $total,
                $this->module->displayName,
                null,
                [],
                $context->getCurrencyId(),
                false,
                $secureKey
            );

            $orderId = $this->module->currentOrder;
        } else {
            $orderId = Tools::getValue('orderId');
            $secureKey = Tools::getValue('secureKey');

            if (empty($secureKey)) {
                $this->displayError([$this->module->l('Missing secure key.')]);

                return false;
            }
            $order = new Order($orderId);

            if (!Validate::isLoadedObject($order)) {
                $errorMessage = $this->module->l('Order not found.');
                $this->get('btipay.logger')->error($errorMessage);
                $this->displayError([$errorMessage]);

                return false;
            }

            if ($order->secure_key !== $secureKey) {
                $errorMessage = $this->module->l('Invalid secure key.');
                $this->get('btipay.logger')->error($errorMessage);
                $this->displayError([$errorMessage], $orderId);

                return false;
            }
        }

        /* @var \BTiPay\Command\ActionCommand $orderCommand */
        if ($btConfig->getPhase() == BTiPayConfig::ONE_PHASE) {
            $orderCommand = $this->get('btipay.order.command');
        } else {
            $orderCommand = $this->get('btipay.authorize.command');
        }

        try {
            $useNewCard = Tools::getValue('bt_ipay_use_new_card', 'no') === 'yes';
            $saveCard = Tools::getValue('bt_ipay_save_cards', 'no') === 'save';
            $selectedCardId = Tools::getValue('bt_ipay_card_id');
            $cardOnFileEnabled = $btConfig->isCardOnFileEnabled();

            /** @var RegisterResponseModel $response */
            $response = $orderCommand->execute([
                'orderId' => $orderId,
                'useNewCard' => $useNewCard,
                'saveCard' => $saveCard,
                'selectedCardId' => $selectedCardId,
                'cardOnFileEnabled' => $cardOnFileEnabled,
                'context' => $context,
                'secureKey' => $secureKey,
            ]);

            if ($response->isError()) {
                $errors[] = $response->getErrorCode() . ': ' . $this->module->l($response->getErrorMessage());
                $btLogger->error($response->getErrorCode() . ': ' . $response->getErrorMessage());
            }

            if ($response->hasRedirect()) {
                Tools::redirect($response->getRedirectUrl());
            }
        } catch (BTiPay\Exception\CommandException $exception) {
            $errors[] = $this->module->l($exception->getMessage());
            $btLogger->error($exception->getMessage());
        } catch (BTransilvania\Api\Exception\ApiException $exception) {
            $errors[] = $this->module->l($exception->getPlainMessage());
            $btLogger->error($exception->getMessage());
        } catch (Exception $exception) {
            $errors[] = $this->module->l('An error occurred. Please contact us for more details.');
            $btLogger->error($exception->getMessage());
        }

        if (!empty($errors)) {
            $this->displayError($errors, $orderId, $secureKey);

            return false;
        }
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $availabilityValidator = $this->get('btipay.validator.availability');
        $params['cart'] = $this->context->cart;
        if (!$availabilityValidator->validate($params)) {
            return false;
        }

        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }

    protected function displayError($errors = [], $invoicenumber = null, $secureKey = null)
    {
        if (empty($errors)) {
            $errorMessage = $this->module->l(
                'Your payment was unsuccessful. Please try again or choose another payment method.'
            );
        } else {
            $errorMessage = implode(PHP_EOL, $errors);
        }
        $this->context->smarty->assign(
            [
                'order_id' => $invoicenumber,
                'errors' => $errorMessage,
                'payment_link' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    [
                        'orderId' => $invoicenumber,
                        'secureKey' => $secureKey,
                    ],
                    true),
            ]
        );

        $this->setTemplate('module:btipay/views/templates/front/error.tpl');
    }
}
