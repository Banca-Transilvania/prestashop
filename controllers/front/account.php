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

use BTiPay\Entity\BTIPayCard;
use BTiPay\Repository\CardRepository;
use BTransilvania\Api\Model\Response\RegisterResponseModel;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * This Controller display cards in customer account
 */
class BtipayAccountModuleFrontController extends ModuleFrontController
{
    /**
     * {@inheritdoc}
     */
    public $auth = true;

    /**
     * {@inheritdoc}
     */
    public $authRedirection = 'my-account';

    /**
     * @var CardRepository
     */
    private $cardRepository;

    /**
     * {@inheritdoc}
     */
    public function initContent()
    {
        parent::initContent();

        /** @var BTiPay\Config\BTiPayConfig $config */
        $config = $this->get('btipay.config');
        $this->cardRepository = $this->get('btipay.card_repository');

        if ($config->isCardOnFileEnabled()) {
            if (Tools::isSubmit('action')) {
                $this->handleCardAction();
            } else {
                $this->displayCards();
            }
        } else {
            Tools::redirect('index.php?controller=404');
        }
    }

    /**
     * Display the saved cards.
     */
    private function displayCards()
    {
        try {
            $savedCards = $this->cardRepository->findByCustomerId($this->context->customer->id);
            $haveCards = is_array($savedCards) && count($savedCards) > 0;
            $token = Tools::getToken(false);

            $this->context->smarty->assign([
                'saved_cards' => $savedCards,
                'have_cards' => $haveCards,
                'moduleDisplayName' => $this->module->displayName,
                'add_card_link' => $this->context->link->getModuleLink(
                    'btipay',
                    'account',
                    [
                        'action' => 'add',
                        'token' => $token,
                    ]
                ),
                'post_action_url' => $this->context->link->getModuleLink('btipay', 'account'),
                'token' => $token, // CSRF token
            ]);

            $this->setTemplate('module:btipay/views/templates/front/account.tpl');
        } catch (Exception $e) {
            $this->handleError($e);
            $this->redirectWithNotifications(
                $this->context->link->getPageLink('my-account', true)
            );
        }
    }

    /**
     * Handle card actions (add, disable, delete)
     */
    private function handleCardAction()
    {
        $action = Tools::getValue('action');
        $cardId = Tools::getValue('cardId');

        // Validate CSRF token
        $token = Tools::getValue('token');
        if (!isset($token) || !$this->isCsrfTokenValid($token)) {
            $this->errors[] = $this->module->l('Invalid CSRF token.');

            return;
        }

        try {
            switch ($action) {
                case 'enable':
                    $this->enableCard($cardId);
                    $this->success[] = $this->module->l('Card enabled successfully.');
                    break;
                case 'disable':
                    $this->disableCard($cardId);
                    $this->success[] = $this->module->l('Card disabled successfully.');
                    break;
                case 'delete':
                    $this->deleteCard($cardId);
                    $this->success[] = $this->module->l('Card deleted successfully.');
                    break;
                case 'add':
                    $redirectUrl = $this->addCard($token);
                    Tools::redirect($redirectUrl);
                    exit;
                case 'returnAddCard':
                    $this->returnAddCard();
                    $this->success[] = $this->module->l('Card added successfully.');
                    break;
                default:
                    $this->errors[] = $this->module->l('Invalid action.');
                    break;
            }
        } catch (Exception $e) {
            $this->handleError($e);
        }

        $this->redirectWithNotifications(
            $this->context->link->getModuleLink('btipay', 'account')
        );
    }

    /**
     * Enable a card by its ID.
     *
     * @param int $cardId
     *
     * @throws Exception
     */
    private function enableCard($cardId)
    {
        $card = $this->getCard($cardId);

        if (!$this->canChangeCard($card)) {
            throw new Exception('Cannot update card');
        }

        /** @var ResponseModelInterface $response */
        $response = $this->toggleStatus($card->ipay_id, true);
        $this->handleApiError($response, 'Unable to enable card');

        $card->status = BTIPayCard::STATUS_ENABLE;
        $this->cardRepository->save($card);
    }

    /**
     * @throws Exception
     */
    private function disableCard($cardId)
    {
        $card = $this->getCard($cardId);

        if (!$this->canChangeCard($card)) {
            throw new Exception('Cannot update card');
        }

        /** @var ResponseModelInterface $response */
        $response = $this->toggleStatus($card->ipay_id, false);
        $this->handleApiError($response, 'Unable to disable card');

        $card->status = BTIPayCard::STATUS_DISABLE;
        $this->cardRepository->save($card);
    }

    /**
     * Delete a card by its ID.
     *
     * @param int $cardId
     *
     * @throws Exception
     */
    private function deleteCard($cardId)
    {
        $card = $this->getCard($cardId);

        if (!$this->canChangeCard($card)) {
            throw new Exception('Cannot delete card');
        }

        if ($card->status == BTIPayCard::STATUS_ENABLE) {
            /** @var ResponseModelInterface $response */
            $response = $this->toggleStatus($card->ipay_id, false);
            $this->handleApiError($response, 'Unable to disable card before deletion');
        }

        $this->cardRepository->deleteById($card->id);
    }

    /**
     * Add a new card.
     *
     * @return string|null Redirect URL
     *
     * @throws Exception
     */
    private function addCard(string $token = '')
    {
        /** @var BTiPay\Facade\Context $context */
        $context = $this->get('btipay.facade.context');
        /** @var BTiPay\Service\CardService $cardService */
        $cardService = $this->get('btipay.card.service');
        /** @var RegisterResponseModel $response */
        $response = $cardService->addCard($context, $token);

        $this->handleApiError($response, 'Unable to add card');

        if ($response->getRedirectUrl() !== null) {
            return $response->getRedirectUrl();
        }
    }

    /**
     * Handle return after adding a card.
     *
     * @throws Exception
     */
    private function returnAddCard()
    {
        $ipayId = Tools::getValue('orderId');

        if (!$ipayId) {
            throw new Exception($this->module->l('Binding Id is missing.'));
        }

        /** @var BTiPay\Service\PaymentDetailsService $paymentDetailsService */
        $paymentDetailsService = $this->get('btipay.payment_details.service');

        /** @var BTransilvania\Api\Model\Response\GetOrderStatusResponseModel $response */
        $response = $paymentDetailsService->get($ipayId);
        if ($response->isSuccess()) {
            if (!$response->canSaveCard()) {
                throw new Exception('Could not save card, invalid data provided - ' . $response->getCustomerError());
            }
            $cardData = $response->getCardInfo();
            if ($cardData === null) {
                return;
            }

            $cardId = $this->cardRepository->getIpayIdByIpayIdCustomer($ipayId, $this->context->customer->id);
            if ($cardId) {
                throw new Exception('This card is already registered');
            }
            $this->cardRepository->create($cardData);
        } else {
            throw new Exception('Could not save card');
        }
    }

    /**
     * Check if card belongs to current customer
     *
     * @param array $card
     *
     * @return bool
     */
    private function canChangeCard(BTIPayCard $card): bool
    {
        return isset($card->customer_id)
            && is_scalar($card->customer_id)
            && (int) $card->customer_id === $this->context->customer->id;
    }

    /**
     * Get card by ID.
     *
     * @param int $cardId
     *
     * @return BTIPayCard
     *
     * @throws Exception
     */
    private function getCard($cardId): BTIPayCard
    {
        $card = $this->cardRepository->findById($cardId);
        if (!$card || !isset($card->ipay_id)) {
            throw new Exception($this->module->l('Cannot find card.'));
        }

        return $card;
    }

    /**
     * Toggle card status.
     *
     * @param string $ipay_card_id
     * @param bool $enable
     *
     * @return ResponseModelInterface
     */
    private function toggleStatus(string $ipay_card_id, bool $enable)
    {
        /** @var BTiPay\Service\CardService $cardService */
        $cardService = $this->get('btipay.card.service');

        return $cardService->toggleCardStatus($ipay_card_id, $enable);
    }

    /**
     * Handle errors by logging them and displaying a generic error message.
     *
     * @param Exception $e
     */
    private function handleError(Exception $e)
    {
        $this->module->getLogger()->error($e->getMessage());
        $this->errors[] = $this->module->l('An unexpected error occurred. Please try again later.');
    }

    /**
     * Handle API response errors by throwing an exception with a detailed message
     *
     * @param ResponseModelInterface $response
     * @param string $customMessage
     *
     * @throws Exception
     */
    private function handleApiError(ResponseModelInterface $response, string $customMessage = '')
    {
        if (!$response->isSuccess()) {
            $errorMessage = 'Error Code: ' . $response->getErrorCode() . ' - ' . $response->getErrorMessage();
            if (!empty($customMessage)) {
                $errorMessage = $customMessage . ': ' . $errorMessage;
            }
            throw new Exception($errorMessage);
        }
    }

    /**
     * Check if the CSRF token is valid.
     *
     * @param string $token
     *
     * @return bool
     */
    protected function isCsrfTokenValid($token)
    {
        return Tools::getToken(false) === $token;
    }
}
