<?php

namespace BTiPay\Request;

use BTiPay\Helper\SubjectReader;
use BTiPay\Repository\CardRepository;
use BTiPay\Request\BuilderInterface;

class SaveCardsRequestBuilder implements BuilderInterface
{
    private $cardRepository;

    public function __construct(CardRepository $cardRepository)
    {
        $this->cardRepository = $cardRepository;
    }

    public function build(array $buildSubject)
    {
        $useNewCard = SubjectReader::readIsNewCard($buildSubject);
        $selectedCardId = SubjectReader::readSelectedCardId($buildSubject);
        $context = SubjectReader::readContext($buildSubject);

        if (!$useNewCard && $selectedCardId) {
            $ipayCardId = $this->cardRepository->getIpayIdByCardIdCustomer($selectedCardId, $context->getCustomerId());
            if($ipayCardId) {
                return ['bindingId' => $ipayCardId];
            }
        }

        return [];
    }
}