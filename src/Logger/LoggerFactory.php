<?php

namespace BTiPay\Logger;

use BTiPay\Config\BTiPayConfig;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

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
