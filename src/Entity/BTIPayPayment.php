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

class BTIPayPayment extends \ObjectModel
{
    public $id;
    public $order_id;
    public $ipay_id;
    public $parent_ipay_id;
    public $ipay_url;
    public $payment_tries;
    public $amount;
    public $capture_amount;
    public $refund_amount;
    public $cancel_amount;
    public $status;
    public $currency;
    public $data;
    public $updated_at;
    public $created_at;

    public static $definition = [
        'table' => 'bt_ipay_payments',
        'primary' => 'id',
        'fields' => [
            'order_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'ipay_id' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'parent_ipay_id' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'ipay_url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'],
            'payment_tries' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'capture_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'refund_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'cancel_amount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'currency' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'data' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
        ],
    ];
}
