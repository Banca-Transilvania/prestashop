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

namespace BTiPay\Helper;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Entity\OrderStateData;
use BTiPay\Exception\CouldNotInstallModuleException;
use BTiPay\Facade\Configuration;
use BTiPay\Facade\Context;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderStateInstaller
{
    /** @var Configuration */
    private Configuration $configurationAdapter;

    public function __construct(
        ?Configuration $configurationAdapter = null
    ) {
        if (!$configurationAdapter) {
            $configurationAdapter = new Configuration(new Context());
        }
        $this->configurationAdapter = $configurationAdapter;
    }

    /**
     * @throws CouldNotInstallModuleException
     */
    public function install(): bool
    {
        $this->installOrderState(
            BTiPayConfig::BTIPAY_STATUS_AWAITING,
            new OrderStateData(
                'Awaiting BT iPay payment',
                '#4169E1'
            )
        );

        $this->installOrderState(
            BTiPayConfig::BTIPAY_STATUS_APPROVED,
            new OrderStateData(
                'Authorized BT iPay. Awaiting Capture.',
                '#3498D8',
                true,
                true,
                false,
                false,
                false,
                false,
                'payment',
                false
            )
        );

        $this->installOrderState(
            BTiPayConfig::BTIPAY_STATUS_PARTIAL_REFUND,
            new OrderStateData(
                'Partial Refund BT iPay',
                '#01B887'
            )
        );

        $this->installOrderState(
            BTiPayConfig::BTIPAY_STATUS_PARTIAL_CAPTURE,
            new OrderStateData(
                'Partial Capture BT iPay',
                '#3498D8',
                true,
                true,
                false,
                true,
                false,
                false,
                'payment',
                false
            )
        );

        return true;
    }

    /**
     * @throws CouldNotInstallModuleException
     */
    private function installOrderState(string $orderStatus, OrderStateData $orderStateInstallerData): void
    {
        if ($this->validateIfStatusExists($orderStatus)) {
            $this->enableState($orderStatus);

            return;
        }

        $orderState = $this->createOrderState($orderStateInstallerData);

        $this->updateStateConfiguration($orderStatus, $orderState);
    }

    private function validateIfStatusExists(string $key): bool
    {
        $existingStateId = (int) $this->configurationAdapter->get($key);
        $orderState = new \OrderState($existingStateId);

        // if state already existed we won't install new one.
        return \Validate::isLoadedObject($orderState);
    }

    /**
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    private function enableState(string $key): void
    {
        $existingStateId = (int) $this->configurationAdapter->get($key);
        $orderState = new \OrderState($existingStateId);

        if ((bool) !$orderState->deleted) {
            return;
        }

        $orderState->deleted = false;
        $orderState->save();
    }

    /**
     * @throws CouldNotInstallModuleException
     */
    private function createOrderState(OrderStateData $orderStateInstallerData): \OrderState
    {
        $orderState = new \OrderState();

        $orderState->send_email = $orderStateInstallerData->isSendEmail();
        $orderState->color = $orderStateInstallerData->getColor();
        $orderState->delivery = $orderStateInstallerData->isDelivery();
        $orderState->logable = $orderStateInstallerData->isLogable();
        $orderState->invoice = $orderStateInstallerData->isInvoice();
        $orderState->module_name = $orderStateInstallerData->getModuleName();
        $orderState->shipped = $orderStateInstallerData->isShipped();
        $orderState->paid = $orderStateInstallerData->isPaid();
        $orderState->template = $orderStateInstallerData->getTemplate();
        $orderState->pdf_invoice = $orderStateInstallerData->isPdfInvoice();
        $orderState->hidden = false;
        $orderState->unremovable = false;

        $languages = \Language::getLanguages();

        foreach ($languages as $language) {
            $orderState->name[$language['id_lang']] = $orderStateInstallerData->getName();
        }

        try {
            $orderState->add();
        } catch (\Exception $exception) {
            throw new CouldNotInstallModuleException('Order state: ' . $orderStateInstallerData->getName() . 'can not be installed. ' . $exception->getMessage());
        }

        $this->createOrderStateLogo($orderState->id);

        return $orderState;
    }

    /**
     * @param int $orderStateId
     */
    public function createOrderStateLogo(int $orderStateId): void
    {
        $source = _PS_MODULE_DIR_ . 'btipay/views/img/logo.png';
        $destination = _PS_ORDER_STATE_IMG_DIR_ . $orderStateId . '.gif';
        @copy($source, $destination);
    }

    private function updateStateConfiguration(string $key, \OrderState $orderState): void
    {
        $this->configurationAdapter->updateValue($key, (int) $orderState->id);
    }
}
