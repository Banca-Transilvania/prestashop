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

namespace BTiPay\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Abstract admin controller providing compatibility across PrestaShop versions.
 *
 * - PrestaShop 9+: Uses PrestaShopAdminController (FrameworkBundleAdminController is deprecated)
 * - PrestaShop 7.x-8.x: Uses FrameworkBundleAdminController
 *
 * This approach ensures the module works without deprecation warnings on PS9
 * while maintaining full backward compatibility with older versions.
 */
if (class_exists(\PrestaShopBundle\Controller\Admin\PrestaShopAdminController::class)) {
    /**
     * Base controller for PrestaShop 9+
     */
    abstract class AbstractAdminController extends \PrestaShopBundle\Controller\Admin\PrestaShopAdminController
    {
    }
} else {
    /**
     * Base controller for PrestaShop 7.x-8.x
     */
    abstract class AbstractAdminController extends \PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController
    {
    }
}
