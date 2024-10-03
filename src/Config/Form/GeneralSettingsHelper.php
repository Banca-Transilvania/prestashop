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
use BTiPay\Helper\Encrypt;
use Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GeneralSettingsHelper
{
    /**
     * @var \Module
     */
    protected $module;

    /**
     * @var \Context
     */
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
        if (((bool) \Tools::isSubmit('submitGeneralSettings')) !== true) {
            return false;
        }

        $form_values = $this->getConfigFormValues();

        $passwordFields = [BTiPayConfig::LIVE_PASSWORD, BTiPayConfig::TEST_PASSWORD, BTiPayConfig::CALLBACK_KEY,
            BTiPayConfig::TEST_USERNAME, BTiPayConfig::LIVE_USERNAME];

        foreach ($form_values as $key => $originalValue) {
            $newValue = \Tools::getValue($key);
            if (empty($newValue) && in_array($key, $passwordFields)) {
                continue;
            }

            // Encrypt the value if it's a password field
            if (in_array($key, $passwordFields)) {
                try {
                    $newValue = Encrypt::encryptConfigValue($newValue);
                } catch (\Exception $e) {
                    // Handle encryption error (optional: log the error, notify the user, etc.)
                    exit('Encryption error: ' . $e->getMessage());
                }
            }

            if ($newValue !== $originalValue || \Configuration::get($key) === false) {
                \Configuration::updateValue($key, $newValue);
            }
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
        $helper->submit_action = 'submitGeneralSettings';
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
                    'title' => $this->module->l('General Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Enabled'),
                        'name' => BTiPayConfig::ENABLED,
                        'is_bool' => true,
                        'desc' => $this->module->l('Enable or disable the payment method'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('No'),
                            ],
                        ],
                        'default' => '0', // Defaults to No
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Title'),
                        'name' => BTiPayConfig::TITLE,
                        'desc' => $this->module->l('The title for the BT IPay payment method.'),
                        'default' => 'BT IPay',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Description'),
                        'name' => BTiPayConfig::DESCRIPTION,
                        'desc' => $this->module->l('Description for transaction. You can use the next variables: $orderId, $shopUrl.'),
                        'default' => 'Comanda nr. $orderId prin iPay BT la: $shopUrl',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Test Mode'),
                        'name' => BTiPayConfig::TEST_MODE,
                        'is_bool' => true,
                        'desc' => $this->module->l('Enable test mode for transactions'),
                        'values' => [
                            [
                                'id' => 'test_on',
                                'value' => true,
                                'label' => $this->module->l('Yes'),
                            ],
                            [
                                'id' => 'test_off',
                                'value' => false,
                                'label' => $this->module->l('No'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('User name (live)'),
                        'name' => BTiPayConfig::LIVE_USERNAME,
                        'desc' => $this->module->l('Username for live mode provided by BT'),
                        'form_group_class' => 'live',
                        'prefix' => '<i class="icon icon-user"></i>',
                    ],
                    [
                        'type' => 'password',
                        'label' => $this->module->l('Password (live)'),
                        'name' => BTiPayConfig::LIVE_PASSWORD,
                        'desc' => $this->module->l('Password for live mode. Will be encrypted in the DB.'),
                        'form_group_class' => 'live',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Sub Merchant ID (live)'),
                        'name' => BTiPayConfig::LIVE_SUB_MERCHANT_ID,
                        'form_group_class' => 'live',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('User name (test)'),
                        'name' => BTiPayConfig::TEST_USERNAME,
                        'desc' => $this->module->l('Username for test mode provided by BT'),
                        'form_group_class' => 'test',
                        'prefix' => '<i class="icon icon-user"></i>',
                    ],
                    [
                        'type' => 'password',
                        'label' => $this->module->l('Password (test)'),
                        'name' => BTiPayConfig::TEST_PASSWORD,
                        'desc' => $this->module->l('Password for test mode. Will be encrypted in the DB.'),
                        'form_group_class' => 'test',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Sub Merchant ID (test)'),
                        'name' => BTiPayConfig::TEST_SUB_MERCHANT_ID,
                        'form_group_class' => 'test',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Callback URL'),
                        'name' => BTiPayConfig::CALLBACK_URL,
                        'desc' => $this->module->l('The url required in order use the callback functionality'),
                        'prefix' => '<i class="icon icon-link"></i>',
                        'disabled' => true,
                    ],
                    [
                        'type' => 'password',
                        'label' => $this->module->l('Callback Key'),
                        'name' => BTiPayConfig::CALLBACK_KEY,
                        'desc' => $this->module->l('The key required in order to verify the callback response'),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save General Settings'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     *
     * @throws \Exception
     */
    public function getConfigFormValues()
    {
        return [
            // General Settings
            BTiPayConfig::ENABLED => $this->getConfigFormValue(BTiPayConfig::ENABLED, false),
            BTiPayConfig::TITLE => $this->getConfigFormValue(BTiPayConfig::TITLE, 'BT IPay'),
            BTiPayConfig::DESCRIPTION => $this->getConfigFormValue(BTiPayConfig::DESCRIPTION, 'Comanda nr. $orderId prin iPay BT la: $shopUrl'),
            BTiPayConfig::TEST_MODE => $this->getConfigFormValue(BTiPayConfig::TEST_MODE, false),
            BTiPayConfig::LIVE_USERNAME => Encrypt::decryptConfigValue($this->getConfigFormValue(BTiPayConfig::LIVE_USERNAME, '')),
            BTiPayConfig::LIVE_PASSWORD => $this->getConfigFormValue(BTiPayConfig::LIVE_PASSWORD, ''),
            BTiPayConfig::LIVE_SUB_MERCHANT_ID => $this->getConfigFormValue(BTiPayConfig::LIVE_SUB_MERCHANT_ID, ''),
            BTiPayConfig::TEST_USERNAME => Encrypt::decryptConfigValue($this->getConfigFormValue(BTiPayConfig::TEST_USERNAME, '')),
            BTiPayConfig::TEST_PASSWORD => $this->getConfigFormValue(BTiPayConfig::TEST_PASSWORD, ''),
            BTiPayConfig::TEST_SUB_MERCHANT_ID => $this->getConfigFormValue(BTiPayConfig::TEST_SUB_MERCHANT_ID, ''),
            BTiPayConfig::CALLBACK_URL => $this->getWebhookUrl(),
            BTiPayConfig::CALLBACK_KEY => $this->getConfigFormValue(BTiPayConfig::CALLBACK_KEY, ''),
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

    public function getWebhookUrl()
    {
        return $this->context->link->getModuleLink('btipay', 'webhook', [], true);
    }
}
