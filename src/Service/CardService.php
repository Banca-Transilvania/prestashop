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

namespace BTiPay\Service;

use BTiPay\Facade\Context;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CardService
{
    private \BTiPay\Client\ClientInterface $client;

    public function __construct(\BTiPay\Client\ClientInterface $client)
    {
        $this->client = $client;
    }

    public function toggleCardStatus($ipay_card_id, bool $enable)
    {
        if ($enable) {
            return $this->client->bindCard(['bindingId' => $ipay_card_id]);
        } else {
            return $this->client->unBindCard(['bindingId' => $ipay_card_id]);
        }
    }

    public function addCard(Context $context, string $token = '')
    {
        $payload = [
            'orderNumber' => preg_replace('/\s+/', '_', 'CARD' . microtime()),
            'amount' => 0,
            'currency' => $context->getCurrencyIso(),
            'returnUrl' => $context->getModuleLink(
                'btipay',
                'account',
                [
                    'action' => 'returnAddCard',
                    'token' => $token,
                ]),
            'clientId' => $context->getCustomerId(),
            'description' => $context->trans('Save card for later use', [], 'Modules.Btipay.Shop'),
        ];

        return $this->client->placeRequest('order', $payload);
    }
}
