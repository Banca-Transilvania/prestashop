{**
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
 *}

<form action="{$action}" id="btipay-payment-form" method="post" class="form-horizontal">
    <p>{l s='You will be redirected to Banca Transilvania page to complete the payment.' mod='btipay'}</p>
    {if $is_customer}
        {if $have_cards}
            <div class="bt-ipay-use-new-card">
                <input type="checkbox" name="bt_ipay_use_new_card" id="bt_ipay_use_new_card" value="yes">
                <label for="bt_ipay_use_new_card">{l s='I want to pay with a new card' mod='btipay'}</label>
                <input type="hidden" name="bt_ipay_use_new_card_hidden" id="bt_ipay_use_new_card_hidden" value="no">
            </div>
            <div class="bt-ipay-card-list">
                <label for="bt-card_id">{l s='Select saved card' mod='btipay'}</label>
                <select name="bt_ipay_card_id" id="bt-card_id" class="bt-ipay-card-select">
                    {foreach from=$saved_cards item=card}
                        <option value="{$card.id}">{$card.pan} - {$card.cardholderName}</option>
                    {/foreach}
                </select>
            </div>
        {/if}
        <div class="bt-save-card-radio" {if $have_cards}style="display:none"{/if}>
            <input type="checkbox" name="bt_ipay_save_cards" id="bt_ipay_save_cards" value="save">
            <label for="bt_ipay_save_cards">{l s='Save my card for future uses' mod='btipay'}</label>
            <input type="hidden" name="bt_ipay_save_cards_hidden" id="bt_ipay_save_cards_hidden" value="no">
        </div>
    {/if}
    <input type="hidden" name="token" value="{$token}">
</form>