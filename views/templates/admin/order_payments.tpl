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
{if $payments|count > 0}
    <style>
        .status-label {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            color: #fff;
        }
        .status-created { background-color: gray; }
        .status-pending { background-color: orange; }
        .status-approved { background-color: green; }
        .status-declined { background-color: red; }
        .status-reversed { background-color: purple; }
        .status-deposited { background-color: blue; }
        .status-partially_refunded { background-color: lightblue; }
        .status-refunded { background-color: darkblue; }
        .status-validation_finished { background-color: gold; color: black;}
    </style>
    <div class="card mt-2">
        <div class="card-header">
            <h3>{l s='BT iPay Payments' mod='btipay'}</h3>
        </div>
        <div class="payment-details-url mb-3 ml-3 d-flex justify-content-start align-items-center">
            <p class="mb-0 mr-3"><strong>IPay URL:</strong> <a href="{$payments[0].ipay_url}" target="_blank">Link</a></p>
            <p class="mb-0 mr-3"><strong>Payment Tries:</strong> {$payments[0].payment_tries}</p>
            {if $payments[0].status === 'DECLINED' OR $payments[0].status === 'PENDING'}
                <p class="mb-0"><strong>Retry Order Pay URL:</strong> <a href="{$payment_link}" target="_blank">Link</a></p>
            {/if}
        </div>

        <div class="card-body">
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Payment ID' mod='btipay'}</th>
                    <th>{l s='IPay ID' mod='btipay'}</th>
                    <th>{l s='Amount' mod='btipay'}</th>
                    <th>{l s='Capture Amount' mod='btipay'}</th>
                    <th>{l s='Refund Amount' mod='btipay'}</th>
                    <th>{l s='Cancel Amount' mod='btipay'}</th>
                    <th>{l s='Status' mod='btipay'}</th>
                    <th>{l s='Type' mod='btipay'}</th>
                    <th>{l s='Updated Date' mod='btipay'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$payments item=payment}
                    <tr>
                        <td>{$payment.id}</td>
                        <td>{$payment.ipay_id}</td>
                        <td>{$payment.amount}</td>
                        <td>{$payment.capture_amount}</td>
                        <td>{$payment.refund_amount}</td>
                        <td>{$payment.cancel_amount}</td>
                        <td><span class="status-label status-{$payment.status|lower}">{$payment.status}</span></td>
                        <td>
                            {if $payment.currency == 'LOY'}
                                <i class="material-icons" style="color: royalblue;">military_tech</i>
                            {else}
                                <i class="material-icons" style="color: royalblue;">payment</i>
                            {/if}
                        </td>
                        <td>{$payment.updated_at|date_format:"%Y-%m-%d %H:%M:%S"}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
{else}
    <p class="alert alert-info">{l s='No BT iPay payments found for this order.' mod='btipay'}</p>
{/if}