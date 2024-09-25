<?php

namespace BTiPay\Repository;

use BTiPay\Entity\BTIPayRefund;
use Db;
use PrestaShopDatabaseException;

class RefundRepository
{
    /**
     * Finds all refunds by order ID.
     *
     * @param int $orderId
     * @return array|BTIPayRefund[]
     */
    public function findAllRefundsByOrderId(int $orderId)
    {
        return $this->findBy('order_id', $orderId);
    }

    /**
     * Finds all refunds by order ID and returns them as an array.
     *
     * @param int $orderId
     * @return array|BTIPayRefund[]
     */
    public function findAllRefundsByOrderIdArray(int $orderId): array
    {
        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from('bt_ipay_refunds');
        $sql->where('order_id = ' . (int)$orderId);

        $results = \Db::getInstance()->executeS($sql);

        if (!$results) {
            return [];
        }

        return $results;
    }

    /**
     * Get the sum of all refunded amounts with status 'success' by order ID.
     *
     * @param int $orderId
     * @return float
     */
    public function getTotalRefundedAmountByOrderId(int $orderId): float
    {
        $sql = new \DbQuery();
        $sql->select('SUM(amount) as total_refunded');
        $sql->from('bt_ipay_refunds');
        $sql->where('order_id = ' . (int)$orderId);
        $sql->where('status = "success"');

        $result = \Db::getInstance()->getValue($sql);

        if (!$result) {
            return 0.0;
        }

        return (float)$result;
    }

    /**
     * Get the sum of all refunded amounts with status 'success' by order ID.
     *
     * @param int $orderId
     * @return float
     */
    public function getTotalRefundedAmountByIpayId(string $ipayId): float
    {
        $sql = new \DbQuery();
        $sql->select('SUM(amount) as total_refunded');
        $sql->from('bt_ipay_refunds');
        $sql->where('ipay_id = \'' . pSQL($ipayId) . '\'');
        $sql->where('status = "success"');

        $result = \Db::getInstance()->getValue($sql);

        if (!$result) {
            return 0.0;
        }

        return (float)$result;
    }


    /**
     * Finds all refunds by IPay ID.
     *
     * @param string $iPayId
     * @return array|BTIPayRefund[]
     */
    public function findAllRefundsByIPayId(string $iPayId)
    {
        return $this->findBy('ipay_id', $iPayId);
    }

    /**
     * Finds a refund by Return ID.
     *
     * @param int $returnId
     * @return BTIPayRefund|null
     */
    public function findRefundByReturnId(int $returnId)
    {
        $result = $this->findBy('return_id', $returnId);
        return !empty($result) ? array_shift($result) : null;
    }

    /**
     * Saves a refund object.
     *
     * @param BTIPayRefund $refund
     * @return bool
     * @throws PrestaShopDatabaseException
     */
    public function save(BTIPayRefund $refund)
    {
        return $refund->save();
    }

    /**
     * General method to find refunds by a field.
     *
     * @param string $field
     * @param mixed $value
     * @return array|BTIPayRefund[]
     */
    private function findBy($field, $value)
    {
        if (!in_array($field, ['order_id', 'ipay_id', 'return_id'])) {
            throw new \InvalidArgumentException('Invalid field specified');
        }

        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from(BTIPayRefund::$definition['table']);
        $sql->where("`$field` = '" . pSQL($value) . "'");

        $results = \Db::getInstance()->executeS($sql);
        $refunds = [];

        if ($results) {
            foreach ($results as $row) {
                $refund = new BTIPayRefund();
                $refund->hydrate($row);
                $refunds[] = $refund;
            }
        }
        return $refunds;
    }
}
