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

namespace BTiPay\Validator\Availability;

use BTiPay\Validator\ValidatorInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class GeneralValidator implements ValidatorInterface
{
    public function validate($params, $config = null): bool
    {
        if (empty($config) || !$config->isEnabled()) {
            return false;
        }

        if ($config->isTestMode()) {
            if (empty($config->getTestUsername())
                || empty($config->getTestPassword())) {
                return false;
            }
        } else {
            if (empty($config->getLiveUsername())
                || empty($config->getLivePassword())) {
                return false;
            }
        }

        return true;
    }
}
