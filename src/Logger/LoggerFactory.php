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

namespace BTiPay\Logger;

use BTiPay\Config\BTiPayConfig;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

if (!defined('_PS_VERSION_')) {
    exit;
}

class LoggerFactory
{
    const MAX_FILES = 90;

    public static function createLogger($channelName)
    {
        if (!\Configuration::get(BTiPayConfig::LOGGING)) {
            // Return a null logger or a logger that does nothing
            return new Logger($channelName, [new \Monolog\Handler\NullHandler()]);
        }

        $logDir = _PS_ROOT_DIR_ . '/var/logs/btipay';

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $logDir = _PS_ROOT_DIR_ . '/log/btipay';
        } elseif (version_compare(_PS_VERSION_, '1.7.4', '<')) {
            $logDir = _PS_ROOT_DIR_ . '/app/logs/btipay';
        }

        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
        }

        $logger = new Logger($channelName);
        $rotatingHandler = new RotatingFileHandler(
            $logDir . '/btipay.log',
            static::MAX_FILES,
            Logger::DEBUG
        );
        $logger->pushHandler($rotatingHandler);

        return $logger;
    }
}
