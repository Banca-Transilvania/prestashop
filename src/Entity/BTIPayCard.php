<?php

namespace BTiPay\Entity;

use ObjectModel;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BTIPayCard extends ObjectModel
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

    public static $definition = array(
        'table'   => 'bt_ipay_cards',
        'primary' => 'id',
        'fields'  => array(
            'customer_id'    => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'ipay_id'        => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'expiration'     => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'cardholderName' => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'pan'            => array('type' => self::TYPE_STRING, 'validate' => 'isString'),
            'status'         => array('type' => self::TYPE_STRING, 'validate' => 'isGenericName'),
            'updated_at'     => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
            'created_at'     => array('type' => self::TYPE_DATE, 'validate' => 'isDateFormat'),
        ),
    );
}