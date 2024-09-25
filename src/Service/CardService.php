<?php

namespace BTiPay\Service;

use BTiPay\Facade\Context;

class CardService
{
    private \BTiPay\Client\ClientInterface $client;

    public function __construct(\BTiPay\Client\ClientInterface $client)
    {
        $this->client = $client;
    }

    /** @inheritDoc */
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
            'amount'      => 0,
            'currency'    => $context->getCurrencyIso(),
            'returnUrl'   => $context->getModuleLink(
                'btipay',
                'account',
                [
                    'action' => 'returnAddCard',
                    'token'  => $token
                ]),
            'clientId'    => $context->getCustomerId(),
            'description' => $context->trans('Save card for later use', [], 'Modules.Btipay.Shop'),
        ];

        return $this->client->placeRequest('order', $payload);
    }
}