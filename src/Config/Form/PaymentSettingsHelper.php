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

namespace BTiPay\Config\Form;

use BTiPay\Config\BTiPayConfig;
use Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentSettingsHelper
{
    protected $module;
    protected $context;

    public function __construct($btIpayModule)
    {
        $this->module = $btIpayModule;
        $this->context = \Context::getContext();
    }

    /**
     * Save form data.
     */
    public function processSettings()
    {
        if (((bool) \Tools::isSubmit('submitPaymentSettings')) !== true) {
            return false;
        }

        $form_values = $this->getConfigFormValues();

        $specific_countries = \Tools::getValue(BTiPayConfig::SPECIFIC_COUNTRIES) !== false ?
            \Tools::getValue(BTiPayConfig::SPECIFIC_COUNTRIES) : $form_values[BTiPayConfig::SPECIFIC_COUNTRIES] ?? null;
        $allowed_currencies = \Tools::getValue(BTiPayConfig::ALLOWED_CURRENCIES) !== false ?
            \Tools::getValue(BTiPayConfig::ALLOWED_CURRENCIES) : $form_values[BTiPayConfig::ALLOWED_CURRENCIES] ?? null;

        $specific_countries = $specific_countries ? implode(',', $specific_countries) : null;
        $allowed_currencies = $allowed_currencies ? implode(',', $allowed_currencies) : null;

        \Configuration::updateValue(BTiPayConfig::SPECIFIC_COUNTRIES, $specific_countries);
        \Configuration::updateValue(BTiPayConfig::ALLOWED_CURRENCIES, $allowed_currencies);

        unset($form_values[BTiPayConfig::SPECIFIC_COUNTRIES . '[]']);
        unset($form_values[BTiPayConfig::ALLOWED_CURRENCIES . '[]']);

        foreach ($form_values as $key => $originalValue) {
            \Configuration::updateValue($key, \Tools::getValue($key));
        }

        return true;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function renderSettingsForm()
    {
        $helper = new \HelperForm();

        $helper->show_toolbar = false;
        $helper->table = 'module';
        $helper->module = $this->module;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = \Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = 'id_module';
        $helper->submit_action = 'submitPaymentSettings';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name;
        $helper->token = \Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Payment Method Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Payment Flow'),
                        'name' => BTiPayConfig::PHASE,
                        'desc' => $this->module->l('Choose between 1-Phase for immediate settlement suitable for services like insurance or tickets, and 2-Phase for goods requiring confirmation before settlement, like physical products. 1-Phase transactions are automatically deposited (T+1/T+2 days), while 2-Phase transactions require merchant action for capture post-delivery confirmation.'),
                        'options' => [
                            'query' => [
                                ['id' => BTiPayConfig::ONE_PHASE, 'name' => $this->module->l('1-Phase – Immediate Settlement')],
                                ['id' => BTiPayConfig::TWO_PHASE, 'name' => $this->module->l('2-Phase – Post-Delivery Settlement')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('New Order Status'),
                        'name' => BTiPayConfig::NEW_ORDER_STATUS,
                        'desc' => $this->module->l('Default status of a new order.'),
                        'options' => [
                            'query' => \OrderState::getOrderStates($this->context->language->id),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Approved Order Status'),
                        'name' => BTiPayConfig::APPROVED_ORDER_STATUS,
                        'desc' => $this->module->l('Default status of APPROVED transaction. (2 Phase)'),
                        'options' => [
                            'query' => \OrderState::getOrderStates($this->context->language->id),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Partial Capture Status'),
                        'name' => BTiPayConfig::PARTIAL_CAPTURE_ORDER_STATUS,
                        'desc' => $this->module->l('Default status of Partial Capture'),
                        'options' => [
                            'query' => \OrderState::getOrderStates($this->context->language->id),
                            'id' => 'id_order_state',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Available for All Countries'),
                        'name' => BTiPayConfig::ALL_COUNTRIES,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('If no, you will be able to select specific countries.'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Available for Specific Countries'),
                        'name' => BTiPayConfig::SPECIFIC_COUNTRIES,
                        'class' => 'chosen',
                        'multiple' => true,
                        'desc' => $this->module->l('Select countries where this method will be available.'),
                        'options' => [
                            'query' => \Country::getCountries($this->context->language->id),
                            'id' => 'id_country',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Allow For Currencies'),
                        'name' => BTiPayConfig::ALLOWED_CURRENCIES,
                        'class' => 'chosen',
                        'multiple' => true,
                        'desc' => $this->module->l('Select specific currencies allowed for this payment method.'),
                        'options' => [
                            'query' => \Currency::getCurrencies(false, true),
                            'id' => 'id_currency',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Generate Invoice on Successful Payment'),
                        'name' => BTiPayConfig::GEN_INVOICE,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Automatically generate an invoice when the payment is successful.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable Card On File'),
                        'name' => BTiPayConfig::CARD_ON_FILE,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Enable saving card details for future transactions.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enable Logging'),
                        'name' => BTiPayConfig::LOGGING,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Enable detailed logging of payment communication.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Custom Refund Button'),
                        'name' => BTiPayConfig::CUSTOM_REFUND_BUTTON,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Enable new refund button on order view.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Auto Refund'),
                        'name' => BTiPayConfig::AUTO_REFUND,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Enable auto refund on creating Credit Slip.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Refund on Status Change'),
                        'name' => BTiPayConfig::REFUND_ON_STATUS_CHANGE,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Automatically process refunds when the order status changes to a refunded status.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Create Order Slip on Full Refund'),
                        'name' => BTiPayConfig::CREATE_ORDER_SLIP_ON_FULL_REFUND,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => true, 'label' => $this->module->l('Yes')],
                            ['id' => 'active_off', 'value' => false, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Automatically create an order slip when a full refund is issued from admin.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save Payment Settings'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    public function getConfigFormValues()
    {
        $specific_countries_json = $this->getConfigFormValue(BTiPayConfig::SPECIFIC_COUNTRIES, '[]');
        $allowed_currencies_json = $this->getConfigFormValue(BTiPayConfig::ALLOWED_CURRENCIES, '[]');
        $specific_countries = explode(',', $specific_countries_json);
        $allowed_currencies = explode(',', $allowed_currencies_json);

        return [
            // Payment Method Settings
            BTiPayConfig::PHASE => $this->getConfigFormValue(BTiPayConfig::PHASE, 'phase1'),
            BTiPayConfig::NEW_ORDER_STATUS => $this->getConfigFormValue(BTiPayConfig::NEW_ORDER_STATUS, $this->getConfigFormValue(BTiPayConfig::BTIPAY_STATUS_AWAITING, null)),
            BTiPayConfig::APPROVED_ORDER_STATUS => $this->getConfigFormValue(BTiPayConfig::APPROVED_ORDER_STATUS, $this->getConfigFormValue(BTiPayConfig::BTIPAY_STATUS_APPROVED, null)),
            BTiPayConfig::PARTIAL_CAPTURE_ORDER_STATUS => $this->getConfigFormValue(BTiPayConfig::PARTIAL_CAPTURE_ORDER_STATUS, $this->getConfigFormValue(BTiPayConfig::BTIPAY_STATUS_PARTIAL_CAPTURE, null)),
            BTiPayConfig::ALL_COUNTRIES => $this->getConfigFormValue(BTiPayConfig::ALL_COUNTRIES, true),
            BTiPayConfig::GEN_INVOICE => $this->getConfigFormValue(BTiPayConfig::GEN_INVOICE, false),
            BTiPayConfig::CARD_ON_FILE => $this->getConfigFormValue(BTiPayConfig::CARD_ON_FILE, false),
            BTiPayConfig::LOGGING => $this->getConfigFormValue(BTiPayConfig::LOGGING, false),
            BTiPayConfig::CUSTOM_REFUND_BUTTON => $this->getConfigFormValue(BTiPayConfig::CUSTOM_REFUND_BUTTON, false),
            BTiPayConfig::AUTO_REFUND => $this->getConfigFormValue(BTiPayConfig::AUTO_REFUND, false),
            BTiPayConfig::REFUND_ON_STATUS_CHANGE => $this->getConfigFormValue(BTiPayConfig::REFUND_ON_STATUS_CHANGE, false),
            BTiPayConfig::CREATE_ORDER_SLIP_ON_FULL_REFUND => \Configuration::get(BTiPayConfig::CREATE_ORDER_SLIP_ON_FULL_REFUND, false),
            BTiPayConfig::SPECIFIC_COUNTRIES . '[]' => $specific_countries,
            BTiPayConfig::ALLOWED_CURRENCIES . '[]' => $allowed_currencies,
        ];
    }

    public function getConfigFormValue($key, $default)
    {
        $value = \Configuration::get($key);

        if (!empty($value)) {
            return $value;
        }

        return $default;
    }
}
