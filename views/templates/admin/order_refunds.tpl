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
{if $refunds|count > 0}
    <div class="card mt-2">
        <div class="card-header">
            <h3>{l s='BT iPay Refunds' mod='btipay'}</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Return ID' mod='btipay'}</th>
                    <th>{l s='IPay ID' mod='btipay'}</th>
                    <th>{l s='Amount' mod='btipay'}</th>
                    <th>{l s='Status' mod='btipay'}</th>
                    <th>{l s='Type' mod='btipay'}</th>
                    <th>{l s='Loy' mod='btipay'}</th>
                    <th>{l s='Date' mod='btipay'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$refunds item=refund}
                    <tr>
                        <td>{$refund.return_id}</td>
                        <td>{$refund.ipay_id}</td>
                        <td>{$refund.amount}</td>
                        <td>
                            {if $refund.status == 'Success'}
                                <i class="material-icons" style="color: green;">check_circle</i>
                            {else}
                                <i class="material-icons" style="color: red;">cancel</i>
                            {/if}
                        </td>
                        <td>{$refund.type}</td>
                        <td>
                            {if $refund.currency == 'LOY'}
                                <i class="material-icons" style="color: royalblue;">military_tech</i>
                            {else}
                                <i class="material-icons" style="color: royalblue;">payment</i>
                            {/if}
                        </td>
                        <td>{$refund.created_at|date_format:"%Y-%m-%d %H:%M:%S"}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{else}
    <p class="alert alert-info">{l s='No BT iPay refunds found for this order.' mod='btipay'}</p>
{/if}