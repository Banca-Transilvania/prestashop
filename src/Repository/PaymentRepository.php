<?php

namespace BTiPay\Repository;

use BTiPay\Entity\BTIPayPayment;
use BTransilvania\Api\Model\IPayStatuses;
use BTransilvania\Api\Model\Response\ResponseModelInterface;
use Db;
use DbQuery;
use PrestaShopException;

class PaymentRepository
{
    private ?BTIPayPayment $payTransaction = null;
    private ?BTIPayPayment $loyTransaction = null;

    /**
     * Finds all payments by order ID.
     *
     * @param int $orderId The ID of the order.
     * @return BTIPayPayment[] Returns an array of payment objects or an empty array if none found.
     */
    public function findByOrderId($orderId): array
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(BTIPayPayment::$definition['table']);
        $sql->where('`order_id` = ' . (int)$orderId);

        try {
            $results = Db::getInstance()->executeS($sql);
            $payments = [];

            foreach ($results as $result) {
                $payment = new BTIPayPayment();
                $payment->hydrate($result);

                if (isset($result['updated_at'])) {
                    $payment->updated_at = $result['updated_at'];
                }

                $payments[] = $payment;
            }

            return $payments;
        } catch (PrestaShopException $e) {
            // Log error
            return [];
        }
    }

    public function getCombinedStatus(array $payments)
    {
        $payStatus = null;
        $loyStatus = null;
        /** @var BTIPayPayment $payment */
        foreach ($payments as $payment) {
            if ($payment->currency === 'LOY') {
                $loyStatus = $payment->status;
            } else {
                $payStatus = $payment->status;
            }
        }

        return IPayStatuses::getCombinedStatus($payStatus, $loyStatus);
    }

    /**
     * Finds all payments by order ID and returns them as an array of arrays.
     *
     * @param int $orderId The ID of the order.
     * @return array Returns an array of associative arrays containing payment data.
     */
    public function findPaymentsByOrderIdAsArray(int $orderId): array
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(BTIPayPayment::$definition['table']);
        $sql->where('`order_id` = ' . (int)$orderId);

        try {
            $results = Db::getInstance()->executeS($sql);
            if (!$results) {
                return [];
            }

            return $results;
        } catch (PrestaShopException $e) {
            return [];
        }
    }

    public function getOrderByIPayId(string $iPayId): ?\Order
    {
        try {
            $payment = $this->findByIPayId($iPayId);
            if ($payment && $payment->order_id) {
                return new \Order($payment->order_id);
            }
        } catch (\Exception $exception) {
        }

        return null;
    }

    public function getOrderByPayment(BTIPayPayment $payment): ?\Order
    {
        try {
            if ($payment->order_id) {
                return new \Order($payment->order_id);
            }
        } catch (\Exception $exception) {
        }

        return null;
    }

    /**
     * Get the sum of all payments approved amounts with status 'success' by order ID.
     *
     * @param int $orderId
     * @return float
     */
    public function getTotalApprovedAmountByOrderId(int $orderId): float
    {
        $sql = new \DbQuery();
        $sql->select('SUM(amount) as total_approved');
        $sql->from('bt_ipay_payments');
        $sql->where('order_id = ' . (int)$orderId);
        $sql->where('status = "' . IPayStatuses::STATUS_APPROVED . '"');

        $result = \Db::getInstance()->getValue($sql);

        if (!$result) {
            return 0.0;
        }

        return (float)$result;
    }

    /**
     * Get the sum of all payments capture amounts with status 'success' by order ID.
     *
     * @param int $orderId
     * @return float
     */
    public function getTotalCaptureAmountByOrderId(int $orderId): float
    {
        $sql = new \DbQuery();
        $sql->select('SUM(capture_amount) as total_captured');
        $sql->from('bt_ipay_payments');
        $sql->where('order_id = ' . (int)$orderId);
        $statuses = [
            IPayStatuses::STATUS_DEPOSITED,
            IPayStatuses::STATUS_PARTIALLY_REFUNDED,
            IPayStatuses::STATUS_REFUNDED
        ];
        $statusString = implode('", "', $statuses);
        $sql->where('status IN ("' . $statusString . '")');

        $result = \Db::getInstance()->getValue($sql);

        if (!$result) {
            return 0.0;
        }

        return (float)$result;
    }

    /**
     * Finds a payment by IPay ID.
     *
     * @param string $iPayId The ID of the order.
     * @return BTIPayPayment|null Returns the payment object or null if not found.
     */
    public function findByIPayId(string $iPayId): ?BTIPayPayment
    {
        $escapedIPayId = pSQL($iPayId);

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(BTIPayPayment::$definition['table']);
        $sql->where('`ipay_id` = "' . $escapedIPayId . '"');

        try {
            $result = Db::getInstance()->getRow($sql);
            if (!$result) {
                return null;
            }

            $payment = new BTIPayPayment();
            $payment->hydrate($result);
            return $payment;
        } catch (PrestaShopException $e) {
            // Log error
            return null;
        }
    }

    /**
     * Finds a payment by Loy ID.
     *
     * @param string $iPayId The ID of the order.
     * @return BTIPayPayment|null Returns the payment object or null if not found.
     */
    public function findByLoyId(string $iPayId): ?BTIPayPayment
    {
        $escapedIPayId = pSQL($iPayId);

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(BTIPayPayment::$definition['table']);
        $sql->where('`loy_id` = "' . $escapedIPayId . '"');

        try {
            $result = Db::getInstance()->getRow($sql);
            if (!$result) {
                return null;
            }

            $payment = new BTIPayPayment();
            $payment->hydrate($result);
            return $payment;
        } catch (PrestaShopException $e) {
            // Log error
            return null;
        }
    }

    public function save(BTIPayPayment $payment): bool
    {
        try {
            if ($payment->id) {
                return $payment->update();
            } else {
                return $payment->add();
            }
        } catch (PrestaShopException $e) {
            // Handle or log the error
            return false;
        }
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function updateData(BTIPayPayment $payment)
    {
        $payment->update();
    }

    /**
     * Updates a payment object with details from a response model.
     *
     * @param BTIPayPayment $payment The payment object to update.
     * @param ResponseModelInterface $response The response model with payment details.
     */
    public function updatePaymentFromResponse(
        BTIPayPayment $payment,
        ResponseModelInterface $response,
        $ipayId,
        $parentId
    ): void {

        $payment->ipay_id = $ipayId;
        $payment->parent_ipay_id = $parentId;
        $payment->status = $response->getStatus();
        $payment->amount = $response->getAmount();
        $payment->currency = $response->getCurrencyCode();
        $payment->capture_amount = $response->getTotalDepositedAmount();
    }

    public function setPayments(array $payments)
    {
        foreach ($payments as $pay) {
            if (!$this->getPayTransaction() && $pay->currency !== 'LOY') {
                $this->setPayTransaction($pay);
            } elseif (!$this->getLoyTransaction() && $pay->currency === 'LOY') {
                $this->setLoyTransaction($pay);
            }
        }
    }

    public function setPaymentsByOrderId($orderId)
    {
        $payments = $this->findByOrderId($orderId);

        foreach ($payments as $pay) {
            if (!$this->getPayTransaction() && $pay->currency !== 'LOY') {
                $this->setPayTransaction($pay);
            } elseif (!$this->getLoyTransaction() && $pay->currency === 'LOY') {
                $this->setLoyTransaction($pay);
            }
        }
    }

    public function getPayTransaction(): ?BTIPayPayment
    {
        return $this->payTransaction;
    }

    public function setPayTransaction(?BTIPayPayment $payTransaction): void
    {
        $this->payTransaction = $payTransaction;
    }

    public function getLoyTransaction(): ?BTIPayPayment
    {
        return $this->loyTransaction;
    }

    public function setLoyTransaction(?BTIPayPayment $loyTransaction): void
    {
        $this->loyTransaction = $loyTransaction;
    }
}