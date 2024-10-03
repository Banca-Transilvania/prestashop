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

class BTIPayRefund extends \ObjectModel
{
    public const SUCCESS = 'Success';
    public const FAILED = 'Failed';

    public const PARTIAL_REFUND = 'Partial';
    public const FULL_REFUND = 'Full';
    public const NONE_REFUND = 'Failed';

    public $id;
    public $order_id;
    public $return_id;
    public $ipay_id;
    public $amount;
    public $status;
    public $type;
    public $currency;

    public static $definition = [
        'table' => 'bt_ipay_refunds',
        'primary' => 'id',
        'fields' => [
            'order_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'return_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'ipay_id' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'type' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'currency' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
        ],
    ];
}
