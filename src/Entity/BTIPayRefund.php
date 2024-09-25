<?php

namespace BTiPay\Entity;

use ObjectModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BTIPayRefund extends ObjectModel
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
        'table'   => 'bt_ipay_refunds',
        'primary' => 'id',
        'fields'  => [
            'order_id'  => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'return_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'ipay_id'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'amount'    => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true],
            'status'    => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'type'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'currency'  => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true]
        ]
    ];
}
