<?php

namespace BTiPay\Response;

use BTiPay\Exception\CommandException;
use BTiPay\Helper\SubjectReader;
use BTiPay\Repository\CardRepository;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

class SaveCardHandler implements HandlerInterface
{
    private $cardRepository;

    public function __construct(CardRepository $cardRepository)
    {
        $this->cardRepository = $cardRepository;
    }

    /**
     * Handles the saving of cards data from a payment gateway response.
     *
     * @param array $handlingSubject Subject containing the order information.
     * @param GetOrderStatusResponseModel $response Response from the payment gateway.
     * @return void
     * @throws CommandException|\Exception
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $isSaveCard = SubjectReader::readIsSaveCard($handlingSubject);
        if ($isSaveCard && $response->paymentIsAccepted()) {
            $cardData = $response->getCardInfo();
            if ($cardData === null) {
                return;
            }
            $this->cardRepository->create($cardData);
        }
    }
}