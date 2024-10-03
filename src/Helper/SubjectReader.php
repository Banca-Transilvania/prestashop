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

namespace BTiPay\Helper;

use BTiPay\Config\BTiPayConfig;
use BTiPay\Facade\Context;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SubjectReader
{
    /**
     * Reads Order ID from subject
     *
     * @param array $subject
     *
     * @return int
     */
    public static function readOrderId(array $subject): int
    {
        if (!isset($subject['orderId']) || !is_numeric($subject['orderId'])) {
            throw new \InvalidArgumentException('Order Id should be provided and should be numeric');
        }

        return (int) $subject['orderId'];
    }

    /**
     * Reads iPay ID from subject
     *
     * @param array $subject
     *
     * @return string
     */
    public static function readIPayId(array $subject): string
    {
        if (!isset($subject['ipayId']) || !is_string($subject['ipayId'])) {
            throw new \InvalidArgumentException('iPay Id should be provided and should be string');
        }

        return (string) $subject['ipayId'];
    }

    /**
     * Reads is new card from subject
     *
     * @param array $subject
     *
     * @return bool
     */
    public static function readIsNewCard(array $subject): bool
    {
        if (!isset($subject['useNewCard']) || !is_bool($subject['useNewCard'])) {
            throw new \InvalidArgumentException('Use new card should be provided and should be boolean');
        }

        return $subject['useNewCard'];
    }

    /**
     * Reads is saveCard from subject
     *
     * @param array $subject
     *
     * @return bool
     */
    public static function readIsSaveCard(array $subject): bool
    {
        if (!isset($subject['saveCard'])) {
            throw new \InvalidArgumentException('Save card should be provided.');
        }

        return $subject['saveCard'];
    }

    /**
     * Reads is saveCard from subject
     *
     * @param array $subject
     *
     * @return bool
     */
    public static function readSecureKey(array $subject): string
    {
        if (!isset($subject['secureKey'])) {
            throw new \InvalidArgumentException('Secure key should be provided.');
        }

        return $subject['secureKey'];
    }

    /**
     * Reads is new card from subject
     *
     * @param array $subject
     *
     * @return int
     */
    public static function readSelectedCardId(array $subject): int
    {
        if (!isset($subject['selectedCardId']) || !is_scalar($subject['selectedCardId'])) {
            throw new \InvalidArgumentException('Selected card should be provided and should be string/int');
        }

        return (int) $subject['selectedCardId'];
    }

    /**
     * Reads is card on file enabled from subject
     *
     * @param array $subject
     *
     * @return bool
     */
    public static function readIsCardOnFileEnabled(array $subject): bool
    {
        if (!isset($subject['cardOnFileEnabled'])) {
            throw new \InvalidArgumentException('Card On File is not provided.');
        }

        return $subject['cardOnFileEnabled'];
    }

    /**
     * Reads context from subject
     *
     * @param array $subject
     *
     * @return Context
     */
    public static function readContext(array $subject): Context
    {
        if (!isset($subject['context']) || !$subject['context'] instanceof Context) {
            throw new \InvalidArgumentException('Context should be provided');
        }

        return $subject['context'];
    }

    /**
     * Reads response from API from subject
     *
     * @param array $subject
     *
     * @return ResponseModelInterface
     */
    public static function readResponse(array $subject): ResponseModelInterface
    {
        if (!isset($subject['response']) || !$subject['response'] instanceof ResponseModelInterface) {
            throw new \InvalidArgumentException('Response should be provided and should be an instace of ResponseModelInterface');
        }

        return $subject['response'];
    }

    /**
     * Reads Amount from subject
     *
     * @param array $subject
     *
     * @return float
     */
    public static function readAmount(array $subject): float
    {
        if (!isset($subject['amount']) || !is_numeric($subject['amount'])) {
            throw new \InvalidArgumentException('Amount should be provided and should be string');
        }

        return (float) $subject['amount'];
    }

    /**
     * Reads BTiPayConfig Object
     *
     * @param array $subject
     *
     * @return BTiPayConfig|null
     */
    public static function readBTConfiguration(array $subject): ?BTiPayConfig
    {
        return $subject['btPayConfig'] ?? null;
    }
}
