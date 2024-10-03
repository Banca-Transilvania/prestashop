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

namespace BTiPay\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BTIPayCard extends \ObjectModel
{
    public const STATUS_ENABLE = 'enabled';
    public const STATUS_DISABLE = 'disabled';
    public $id;
    public $customer_id;
    public $ipay_id;
    public $expiration;
    public $cardholderName;
    public $pan;
    public $status;
    public $updated_at;
    public $created_at;

    public static $definition = [
        'table' => 'bt_ipay_cards',
        'primary' => 'id',
        'fields' => [
            'customer_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'ipay_id' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'expiration' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'cardholderName' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'pan' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'updated_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'created_at' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ],
    ];
}
