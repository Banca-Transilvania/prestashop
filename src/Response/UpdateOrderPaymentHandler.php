<?php

namespace BTiPay\Response;

use BTiPay\Helper\SubjectReader;
use BTransilvania\Api\Model\Response\GetOrderStatusResponseModel;
use BTransilvania\Api\Model\Response\ResponseModelInterface;
use BTiPay\Service\PaymentDetailsService;

class UpdateOrderPaymentHandler implements HandlerInterface
{
    private PaymentDetailsService $paymentDetailsService;

    public function __construct(PaymentDetailsService $paymentDetailsService)
    {
        $this->paymentDetailsService = $paymentDetailsService;
    }

    /**
     * @param array $handlingSubject
     * @param GetOrderStatusResponseModel $response
     * @return void
     * @throws \Exception
     */
    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        $iPayId = SubjectReader::readIPayId($handlingSubject);
        $orderId = explode("-", $response->orderNumber);
        $orderId = $orderId[0];

        $loyAmount = $response->getLoyAmount();
        $loyId = $response->getLoyId();

        if($loyId) {
            $loyDetails = $this->paymentDetailsService->get($loyId);
            $loyAmount = $loyDetails->getTotalAvailableForRefund();
        }

        $amountPaid = $response->getTotalAvailableForRefund() + $loyAmount;

        $data = [
            'transaction_id' => $iPayId,
            'amount' => $amountPaid,
            'payment_method' => 'BT iPay',
        ];

        $cardInfo = $response->getCardAuthInfo();
        $data['card_number'] = $cardInfo['pan'] ?? null;
        $data['card_brand'] = $cardInfo['card_brand'] ?? $this->getCardTypeByPan($cardInfo['pan']);
        $data['card_expiration'] = $cardInfo['expiration'] ?? null;
        $data['card_holder'] = $cardInfo['cardholderName'] ?? null;

        $this->updateOrderPayment($orderId, $data);
    }

    public function updateOrderPayment($orderId, $data)
    {
        $order = new \Order((int)$orderId);
        if (!\Validate::isLoadedObject($order)) {
            throw new \Exception("Order not found.");
        }

        $orderPayments = \OrderPayment::getByOrderReference($order->reference);
        if (count($orderPayments) > 0) {
            $orderPayment = array_shift($orderPayments); // Assuming we update the first payment
        } else {
            $orderPayment = new \OrderPayment();
            $orderPayment->order_reference = $order->reference;
        }

        foreach ($data as $key => $value) {
            if (property_exists($orderPayment, $key)) {
                $orderPayment->$key = $value;
            }
        }

        $orderPayment->id_currency = $order->id_currency;
        $orderPayment->date_add = date('Y-m-d H:i:s');

        if (!$orderPayment->save()) {
            throw new \Exception("Failed to save payment information.");
        }

        return true;
    }

    function getCardTypeByPan($pan)
    {
        if (empty($pan)) {
            return null;
        }

        $pan = strval($pan);
        $iin = substr($pan, 0, 6);

        $cardTypes = [
            'Visa'             => '/^4[0-9]{5}/',
            'MasterCard'       => '/^(5[1-5][0-9]{4}|2[2-7][0-9]{4})/',
            'American Express' => '/^3[47][0-9]{4}/',
            'Diners Club'      => '/^3(?:0[0-5]|[68][0-9])[0-9]{3}/',
            'Discover'         => '/^6(?:011|5[0-9]{2})[0-9]{3}/',
            'JCB'              => '/^(?:2131|1800|35\d{3})\d{3}/'
        ];

        foreach ($cardTypes as $type => $pattern) {
            if (preg_match($pattern, $iin)) {
                return $type;
            }
        }

        return null;
    }
}