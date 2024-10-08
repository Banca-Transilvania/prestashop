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
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

// Table for storing payment information
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bt_ipay_payments` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `order_id` BIGINT NOT NULL,
                `ipay_id` VARCHAR(255) NOT NULL,
                `parent_ipay_id` VARCHAR(255) NULL,
                `ipay_url` VARCHAR(255) NOT NULL,
                `payment_tries` INT NOT NULL,
                `amount` DECIMAL(15,2) NOT NULL,
                `capture_amount` DECIMAL(15,2) NOT NULL,
                `refund_amount` DECIMAL(15,2) NOT NULL,
                `cancel_amount` DECIMAL(15,2) NOT NULL,
                `status` VARCHAR(255) NOT NULL,
                `currency` CHAR(3) NOT NULL,
                `data` TEXT DEFAULT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `order_id_index` (`order_id`),
                UNIQUE KEY `ipay_id` (`ipay_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Table for storing card details
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bt_ipay_cards` (
    			`id` INT NOT NULL AUTO_INCREMENT ,
				`customer_id` BIGINT NOT NULL ,
				`ipay_id` VARCHAR(255) NOT NULL ,
				`expiration` VARCHAR(255) NOT NULL ,
				`cardholderName` VARCHAR(255) NOT NULL ,
				`pan` VARCHAR(255) NOT NULL ,
				`status` VARCHAR(255) NOT NULL ,
				`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY `id` (`id`),
				INDEX `customer_id` (`customer_id`),
				INDEX `customer_details_index` (`customer_id`, `pan`),
				UNIQUE KEY `ipay_id` (`ipay_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Table for storing refunds
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'bt_ipay_refunds` (
    			`id` INT NOT NULL AUTO_INCREMENT,
                `order_id` BIGINT NOT NULL,
                `return_id` INT NOT NULL,
                `ipay_id` VARCHAR(255) NOT NULL,
                `amount` DECIMAL(15,2) NOT NULL,
                `status` VARCHAR(255) NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `currency` CHAR(3) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `order_idx` (`order_id`),
                UNIQUE KEY `unique_refund` (`order_id`, `return_id`, `ipay_id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        error_log('Failed to execute query: ' . $query);

        return false;
    }
}

return true;
