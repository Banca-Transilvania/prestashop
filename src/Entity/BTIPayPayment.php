<?php

namespace BTiPay\Entity;

use ObjectModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BTIPayPayment extends ObjectModel
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

    public static $definition = array(
        'table'   => 'bt_ipay_payments',
        'primary' => 'id',
        'fields' => array(
            'order_id'       => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'ipay_id'        => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'parent_ipay_id' => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'ipay_url'       => array('type' => self::TYPE_STRING, 'validate' => 'isUrl'),
            'payment_tries'  => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'amount'         => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'capture_amount' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'refund_amount'  => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'cancel_amount'  => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice'),
            'status'         => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'currency'       => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'data'           => array('type' => self::TYPE_HTML, 'validate' => 'isCleanHtml')
        )
    );
}