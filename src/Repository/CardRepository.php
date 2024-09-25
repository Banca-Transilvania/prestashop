<?php

namespace BTiPay\Repository;

use BTiPay\Entity\BTIPayCard;
use Db;
use DbQuery;
use PrestaShopException;
use BTiPay\Helper\Encrypt;

class CardRepository
{
    /**
     * Creates or updates a card in the database.
     */
    public function save(BTIPayCard $card)
    {
        // Encrypt sensitive data before saving
        $cardData = [
            'expiration'     => $card->expiration,
            'cardholderName' => $card->cardholderName,
            'pan'            => $card->pan
        ];

        $encryptedData = Encrypt::encryptCard($cardData);

        $card->expiration = $encryptedData['expiration'];
        $card->cardholderName = $encryptedData['cardholderName'];
        $card->pan = $encryptedData['pan'];

        return $card->save();
    }

    /**
     * Create a new card record in the database after checking for required fields and removing any existing card with the same PAN for a given customer.
     */
    public function create(array $data)
    {
        // Check for mandatory fields
        $requiredFields = ['expiration', 'cardholderName', 'pan', 'ipay_id', 'customer_id'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new \Exception('Invalid data for card storage: Missing ' . $field);
            }
        }

        // Optional: Remove not needed 'approvalCode'
        if (isset($data['approvalCode'])) {
            unset($data['approvalCode']);
        }

        // Encrypt sensitive data before saving
        $encryptedData = Encrypt::encryptCard($data);

        // Remove any existing card with the same PAN and customer ID
        $this->deleteWithSamePan($encryptedData['pan'], (int)$data['customer_id']);

        // Prepare data for insertion
        $encryptedData['status'] = 'enabled'; // Assume 'enabled' is a valid status, adapt as necessary
        $encryptedData['created_at'] = date('Y-m-d H:i:s'); // Set current time for created_at

        // Insert data into the database
        if (!Db::getInstance()->insert('bt_ipay_cards', $encryptedData)) {
            throw new \Exception('Failed to create card record.');
        }
    }

    /**
     * Deletes a card with the same PAN and customer ID to prevent duplicates.
     */
    public function deleteWithSamePan($pan, $customerId)
    {
        return Db::getInstance()->delete('bt_ipay_cards', 'pan = \'' . pSQL($pan) . '\' AND customer_id = ' . (int)$customerId);
    }

    /**
     * Deletes a card by ID.
     */
    public function deleteById($id)
    {
        return Db::getInstance()->delete('bt_ipay_cards', 'id = ' . (int)$id);
    }

    /**
     * Updates the status of a card by its IPAY ID.
     */
    public function updateStatus($status, $ipayCardId)
    {
        return Db::getInstance()->update('bt_ipay_cards', ['status' => pSQL($status)], 'ipay_id = \'' . pSQL($ipayCardId) . '\'');
    }

    /**
     * Finds a card by its ID.
     */
    public function findById($id)
    {
        $card = new BTIPayCard((int)$id);

        // Decrypt sensitive data after retrieving
        $cardData = [
            'expiration' => $card->expiration,
            'cardholderName' => $card->cardholderName,
            'pan' => $card->pan
        ];

        $decryptedData = Encrypt::decryptCard($cardData);

        $card->expiration = $decryptedData['expiration'];
        $card->cardholderName = $decryptedData['cardholderName'];
        $card->pan = $decryptedData['pan'];

        return $card;
    }

    /**
     * Finds a card by IPAY ID.
     */
    public function findByIpayId($ipayId)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('bt_ipay_cards');
        $sql->where('ipay_id = \'' . pSQL($ipayId) . '\'');

        $id = Db::getInstance()->getValue($sql);
        if ($id) {
            return $this->findById((int)$id);
        }

        return null;
    }

    /**
     * Finds cards by customer ID.
     */
    public function findByCustomerId($customerId)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('bt_ipay_cards');
        $sql->where('customer_id = ' . (int)$customerId);
        $sql->orderBy('created_at DESC');

        $results = Db::getInstance()->executeS($sql);

        // Decrypt sensitive data after retrieving
        foreach ($results as &$result) {
            $result = Encrypt::decryptCard($result);
        }

        return $results;
    }

    /**
     * Finds enabled cards by customer ID.
     */
    public function findEnabledByCustomerId($customerId)
    {
        $sql = new DbQuery();
        $sql->select('id, cardholderName, pan');
        $sql->from('bt_ipay_cards');
        $sql->where('customer_id = ' . (int)$customerId . ' AND status = \'enabled\'');
        $sql->orderBy('created_at DESC');

        $results = Db::getInstance()->executeS($sql);

        // Decrypt sensitive data after retrieving
        foreach ($results as &$result) {
            $result = Encrypt::decryptCard($result);
        }

        return $results;
    }

    /**
     * Gets IPAY IDs by customer ID.
     */
    public function getIpayIdsByCustomerId($customerId)
    {
        $sql = new DbQuery();
        $sql->select('ipay_id');
        $sql->from('bt_ipay_cards');
        $sql->where('customer_id = ' . (int)$customerId);

        $results = \Db::getInstance()->executeS($sql);
        return array_column($results, 'ipay_id');
    }

    /**
     * The table name utility.
     */
    public static function tableName()
    {
        return _DB_PREFIX_ . 'bt_ipay_cards';
    }

    /**
     * Get iPay ID Card by Card ID and Customer ID
     *
     * @param int $selectedCardId
     * @param int $customerId
     * @return false|string|null
     */
    public function getIpayIdByCardIdCustomer(int $selectedCardId, int $customerId)
    {
        $sql = new DbQuery();
        $sql->select('ipay_id');
        $sql->from('bt_ipay_cards');
        $sql->where('id = ' . (int)$selectedCardId . ' AND customer_id = ' . (int)$customerId);

        return Db::getInstance()->getValue($sql);
    }

    /**
     * Get iPay ID Card by Card ID and Customer ID
     *
     * @param string $selectedCardId
     * @param int $customerId
     * @return false|string|null
     */
    public function getIpayIdByIpayIdCustomer(string $ipayId, int $customerId)
    {
        $sql = new DbQuery();
        $sql->select('ipay_id');
        $sql->from('bt_ipay_cards');
        $ipayId = pSQL($ipayId);
        $sql->where("ipay_id = '$ipayId' AND customer_id = " . (int)$customerId);

        return Db::getInstance()->getValue($sql);
    }
}