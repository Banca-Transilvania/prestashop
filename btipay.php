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

use BTiPay\Exception\CouldNotInstallModuleException;
use BTiPay\Config\Form\GeneralSettingsHelper;
use BTiPay\Config\Form\PaymentSettingsHelper;
use BTransilvania\Api\Model\IPayStatuses;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Btipay extends PaymentModule
{
    protected $config_form = false;

    /** @var \Monolog\Logger|null */
    private $logger = null;

    public function __construct()
    {
        $this->name = 'btipay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Banca Transilvania';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('BT iPay');
        $this->description = $this->l('BT iPay Prestashop Payment Module. Compatible with Prestashop version 1.7.6 - 8.1.5');

        $this->confirmUninstall = $this->l('Are you sure you want to unistall the payment module BT iPay?');
        $this->limited_currencies = array('RON','EUR', 'USD');

        $this->ps_versions_compliancy = array('min' => '1.7.6', 'max' => _PS_VERSION_);

        $this->generalSettingsHelper = new GeneralSettingsHelper($this);
        $this->paymentSettingsHelper = new PaymentSettingsHelper($this);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (!extension_loaded('curl'))
        {
            $this->_errors[] = $this->trans('You have to enable the cURL extension on your server to install this module', [], 'Modules.Btipay.Admin');
            return false;
        }

        try {
            $orderStateInstaller = new \BTiPay\Helper\OrderStateInstaller();
            $orderStateInstaller->install();
        } catch (CouldNotInstallModuleException $e) {
            $this->getLogger()->error($e->getMessage());
            $this->_errors[] = $this->l('Unable to install BT iPay statuses');

            return false;
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
        }

        Configuration::updateValue('BTIPAY_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        if (!parent::install()) {
            return false;
        }

        $registerHooks = $this->registerHook('header') && // add css & js in front
            $this->registerHook('displayBackOfficeHeader') && // add css & js in admin
            $this->registerHook('paymentOptions') && // Create payment option on checkout
            $this->registerHook('moduleRoutes') && // Register Webhook custom route
            $this->registerHook('actionGetAdminOrderButtons') && // Create admin buttons for Cancel and Capture
            $this->registerHook('displayAdminOrder') && // Add capture/cancel/refund modal
            $this->registerHook('displayAdminOrderMainBottom') && // Display Payments & Refunds on order details
            $this->registerHook('actionOrderStatusUpdate') && // Call Refund when the order changed the status in Refunded
            $this->registerHook('displayCustomerAccount'); // Display Cards on My Account Front

        if (!$registerHooks) {
            $this->uninstall();
            return false;
        }

        $this->clearCache();

        Configuration::updateValue('BTIPAY_CLEAR_CACHE', true);

        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('BTIPAY_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->postProcess();

        $this->context->smarty->assign('module_dir', $this->_path);
        $selected_tab = Tools::getValue('btipay_tab', 'generalSettings');

        $paymentform = $this->paymentSettingsHelper->renderSettingsForm();
        $generalForm = $this->generalSettingsHelper->renderSettingsForm();

        $this->context->smarty->assign([
            'link'        => $this->context->link,
            'generalForm' => $generalForm,
            'paymentform' => $paymentform,
            'selectedTab' => $selected_tab
        ]);

        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if($this->generalSettingsHelper->processSettings()) {
            $this->setSuccessMessage($this->l('General Settings updated.'));
        }

        if($this->paymentSettingsHelper->processSettings()) {
            $this->setSuccessMessage($this->l('Payment Settings updated.'));
        }
    }

    public function setSuccessMessage($message)
    {
        if (!isset($this->context->controller->confirmations)) {
            return;
        }

        $this->context->controller->confirmations[] = $message;
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (\Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }

        if (\Tools::getValue('controller') === 'AdminOrders') {
            $this->context->controller->addJS($this->_path . 'views/js/adminOrder.js');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'views/js/front.js');
        $this->context->controller->addCSS($this->_path.'views/css/front.css');
    }

    /**
     * Return payment options available for PS 1.7+
     *
     * @param array Hook parameters
     *
     * @return array|null
     * @throws Exception
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        if (!$this->isAvailable($params)) {
            return;
        }

        $option = $this->getBTiPayPaymentOption();

        return [
            $option
        ];
    }

    public function hookActionGetAdminOrderButtons(array $params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Action\ActionsBarButtonsCollection $bar */
        $bar = $params['actions_bar_buttons_collection'];

        $paymentRepository = new BTiPay\Repository\PaymentRepository();
        $payments = $paymentRepository->findByOrderId($params['id_order']);

        $paymentStatus = null;
        if (is_array($payments) && !empty($payments)) {
            $paymentStatus = $paymentRepository->getCombinedStatus($payments);
        }

        if ($paymentStatus) {
            $buttons = [];
            if ($paymentStatus == IPayStatuses::STATUS_APPROVED) {
                $buttons['captureButton'] = new \PrestaShop\PrestaShop\Core\Action\ActionsBarButton(
                    'btn-info bt-button', [
                    'type'                => 'button',
                    'data-order-id'       => $params['id_order'],
                    'data-action-command' => 'capture',
                    'data-toggle'         => 'modal',
                    'data-target'         => '#amountModal'
                ], 'Capture BT iPay'
                );

                $buttons['cancelButton'] = new \PrestaShop\PrestaShop\Core\Action\ActionsBarButton(
                    'btn-info bt-button', [
                    'type'                => 'button',
                    'data-order-id'       => $params['id_order'],
                    'data-action-command' => 'cancel',
                    'data-toggle'         => 'modal',
                    'data-target'         => '#amountModal'
                ], 'Cancel BT iPay'
                );
            } elseif (in_array($paymentStatus, [IPayStatuses::STATUS_DEPOSITED, IPayStatuses::STATUS_PARTIALLY_REFUNDED])) {
                try {
                    /** @var \BTiPay\Config\BTiPayConfig $config */
                    $config = $this->get('btipay.config');
                    if ($config->isCustomRefundEnabled()) {
                        $buttons['refundButton'] = new \PrestaShop\PrestaShop\Core\Action\ActionsBarButton(
                            'btn-info bt-button', [
                            'type'                => 'button',
                            'data-order-id'       => $params['id_order'],
                            'data-action-command' => 'refund',
                            'data-toggle'         => 'modal',
                            'data-target'         => '#amountModal'
                        ], 'Refund BT iPay'
                        );
                    }
                } catch (\Exception $e) {
                    $this->clearCache();
                    $this->getLogger()->error($e->getMessage());
                    throw new OrderException(' We have refreshed the cache. Please try to view the order again by refreshing the page.');
                }

            }

            foreach ($buttons as $button) {
                $bar->add($button);
            }
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        $actions = ['capture', 'refund', 'cancel'];
        $api_urls = [];

        try {
            $router = $this->get('router');

            $paymentRepository = new \BTiPay\Repository\PaymentRepository();

            $maxTotalAmountPaid = $paymentRepository->getTotalCaptureAmountByOrderId($params['id_order']);
            $approvedAmount = $paymentRepository->getTotalApprovedAmountByOrderId($params['id_order']);

            $refundRepository = new \BTiPay\Repository\RefundRepository();
            $refundedAmount = $refundRepository->getTotalRefundedAmountByOrderId($params['id_order']);

            $maxTotalAmountPaid -= $refundedAmount;

            foreach ($actions as $action) {
                $api_urls[$action] = $router->generate('btipay_api_payment_handle', [
                    'action'         => $action,
                    'orderId'        => $params['id_order'],
                    'maxTotalAmount' => round($maxTotalAmountPaid, 2)
                ]);
            }
        } catch (\Exception $e) {
            $this->clearCache();
            $this->getLogger()->error($e->getMessage());
            throw new OrderException(' We have refreshed the cache. Please try to view the order again by refreshing the page.');
        }

        $this->context->smarty->assign([
            'order_id'       => $params['id_order'],
            'api_urls'       => $api_urls,
            'maxTotalAmount' => round($maxTotalAmountPaid, 2),
            'approvedAmount' => $approvedAmount
        ]);

        return $this->display(__FILE__, 'views/templates/admin/order_modal.tpl');
    }

    public function hookDisplayAdminOrderMainBottom($params)
    {
        $orderId = $params['id_order'];

        $order = new Order($orderId);
        if (!Validate::isLoadedObject($order)) {
            $this->get('btipay.logger')->error('Order not found for ID: ' . $orderId);
            return $this->displayError('Order not found.');
        }

        $paymentRepository = new \BTiPay\Repository\PaymentRepository();
        $refundRepository = new \BTiPay\Repository\RefundRepository();

        $payments = $paymentRepository->findPaymentsByOrderIdAsArray($orderId);
        $refunds = $refundRepository->findAllRefundsByOrderIdArray($orderId);

        $this->context->smarty->assign([
            'payments'     => $payments,
            'refunds'      => $refunds,
            'payment_link' => $this->context->link->getModuleLink(
                $this->name,
                'payment',
                [
                    'orderId'   => $orderId,
                    'secureKey' => $order->secure_key
                ],
                true)
        ]);

        $paymentsOutput = $this->display(__FILE__, 'views/templates/admin/order_payments.tpl');
        $refundsOutput = $this->display(__FILE__, 'views/templates/admin/order_refunds.tpl');

        return $paymentsOutput . $refundsOutput;
    }

    /**
     * This hook is used to display information in customer account
     *
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayCustomerAccount(array $params)
    {
        /** @var \BTiPay\Config\BTiPayConfig $config */
        $config = $this->get('btipay.config');

        if($config->isCardOnFileEnabled()) {
            $this->context->smarty->assign([
                'moduleDisplayName' => $this->displayName,
                'moduleLogoSrc' => $this->getPathUri() . 'logo.png',
                'cardsLink' => $this->context->link->getModuleLink(
                    $this->name,
                    'account'
                ),
            ]);

            return $this->context->smarty->fetch('module:btipay/views/templates/hook/displayCustomerAccount.tpl');
        }
    }

    public function hookActionOrderStatusUpdate($params)
    {
        /** @var \BTiPay\Config\BTiPayConfig $config */
        $config = $this->get('btipay.config');

        if(!$config->isRefundOnStatusChangeEnabled()) {
            return;
        }

        /** @var OrderState $newOrderStatus */
        $newOrderStatus = $params['newOrderStatus'];
        $order = new Order($params['id_order']);

        $refundedStatusId = Configuration::get('PS_OS_REFUND');

        if ($newOrderStatus->id == $refundedStatusId) {
            $totalRefundAmount = $order->getTotalPaid();

            $data = [
                'order' => $order,
                'type' => 'status_changed',
                'amount' => $totalRefundAmount
            ];

            /** @var BTiPay\Service\RefundService $refundService */
            $refundService = $this->get('btipay.refund.service');
            try {
                $refundService->customRefund($data, $data['type'], $data['amount']);
            } catch (Exception $e) {
                $this->getLogger()->error(sprintf("Failed to process refund for order %s: %s", $order->id, $e->getMessage()));
            }
        }
    }


    /**
     * Factory of PaymentOption for BT iPay
     *
     * @return PaymentOption
     * @throws Exception
     */
    private function getBTiPayPaymentOption(): PaymentOption
    {
        $btiPayOption = new PaymentOption();
        $btiPayOption->setModuleName($this->name);
        $btiPayOption->setCallToActionText($this->l('Pay by BT iPAY'));
        $btiPayOption->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));

        /** @var \BTiPay\Config\BTiPayConfig $config */
        $config = $this->get('btipay.config');

        $savedCards = [];
        $haveCards = false;
        $isCustomer = false;

        if ($config->isCardOnFileEnabled()) {
            if ($this->context->customer->isLogged()) {
                try {
                    /** @var \BTiPay\Repository\CardRepository $cardRepository */
                    $cardRepository = $this->get('btipay.card_repository');
                    $savedCards = $cardRepository->findEnabledByCustomerId($this->context->customer->id);
                    $haveCards = is_array($savedCards) && count($savedCards) > 0;
                } catch (\Exception $e) {
                    $this->get('btipay.logger')->error('Failed to retrieve saved cards: ' . $e->getMessage());
                }

                $isCustomer = true;
            }
        }

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'payment', ['option' => 'btipay'], true),
            'saved_cards' => $savedCards,
            'have_cards' => $haveCards,
            'is_customer' => $isCustomer
        ]);

        $btiPayOption->setForm($this->context->smarty->fetch('module:btipay/views/templates/front/paymentOptionExternal.tpl'));
        $btiPayOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/payment_option2.png'));

        return $btiPayOption;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @throws \Exception
     */
    private function isAvailable($params)
    {
        /** @var \BTiPay\Validator\Availability\AvailabilityValidatorPool $availabilityValidator */
        $availabilityValidator = $this->get('btipay.validator.availability');
        if (!$availabilityValidator->validate($params)) {
            return false;
        }

        return true;
    }

    public function getLogger()
    {
        if ($this->logger === null) {
            if (method_exists($this, 'get') && $this->getContainer()->has('btipay.logger')) {
                $this->logger = $this->get('btipay.logger');
            } else {
                $this->logger = \BTiPay\Logger\LoggerFactory::createLogger('btipay');
            }
        }
        return $this->logger;
    }

    public function hookModuleRoutes($params)
    {
        return array(
            'module-btipay_front-webhook' => array(
                'controller' => 'webhook',
                'rule'       => 'btipay/webhook',
                'keywords'   => array(),
                'params'     => array(
                    'fc'     => 'module',
                    'module' => $this->name,
                    'controller' => 'webhook'
                )
            )
        );
    }

    private function clearCache()
    {
        $this->get('prestashop.core.cache.clearer.cache_clearer_chain')->clear();
    }
}
