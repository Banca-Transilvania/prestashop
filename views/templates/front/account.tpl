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
{extends file='customer/page.tpl'}

{block name='page_title'}
  <h1 class="h1">{$moduleDisplayName} - {l s='My Saved Cards' mod='btipay'}</h1>
{/block}

{block name='page_content'}
  <div class="card-container">
    {if $have_cards}
      <table class="table table-striped table-bordered table-labeled hidden-sm-down table-hover">
        <thead>
        <tr>
          <th>{l s='Cardholder Name' mod='btipay'}</th>
          <th>{l s='Card Number' mod='btipay'}</th>
          <th>{l s='Expiration Date' mod='btipay'}</th>
          <th>{l s='Status' mod='btipay'}</th>
          <th>{l s='Actions' mod='btipay'}</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$saved_cards item=card}
          <tr>
            <td>{$card.cardholderName}</td>
            <td>{$card.pan|substr:0:4} {$card.pan|substr:4:2}** **** {$card.pan|substr:-4}</td>
            <td>{$card.expiration|substr:0:4}/{$card.expiration|substr:4:2}</td>
            <td>
              {if $card.status == 'enabled'}
                <span class="label label-pill bright" style="background-color:#44d834">
                {$card.status}
              </span>
              {else}
                <span class="label label-pill bright" style="background-color:#d40000">
                {$card.status}
              </span>
              {/if}

            </td>
            <td>
              {if $card.status == 'enabled'}
                <form action="{$post_action_url}" method="post" style="display:inline;">
                  <input type="hidden" name="action" value="disable">
                  <input type="hidden" name="cardId" value="{$card.id}">
                  <input type="hidden" name="token" value="{$token}">
                  <button type="submit" class="btn btn-secondary">{l s='Disable' mod='btipay'}</button>
                </form>
              {else}
                <form action="{$post_action_url}" method="post" style="display:inline;">
                  <input type="hidden" name="action" value="enable">
                  <input type="hidden" name="cardId" value="{$card.id}">
                  <input type="hidden" name="token" value="{$token}">
                  <button type="submit" class="btn btn-secondary">{l s='Enable' mod='btipay'}</button>
                </form>
              {/if}
              <form action="{$post_action_url}" method="post" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="cardId" value="{$card.id}">
                <input type="hidden" name="token" value="{$token}">
                <button type="submit" class="btn btn-danger">{l s='Delete' mod='btipay'}</button>
              </form>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    {else}
      <div class="alert alert-info">{l s='No cards saved.' mod='btipay'}</div>
    {/if}
    <a href="{$add_card_link}" class="btn btn-primary">{l s='Add Card' mod='btipay'}</a>
  </div>
{/block}
