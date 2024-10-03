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
{extends file='page.tpl'}

{block name='page_content'}
	<div class="content-container">
		<h3>{l s='Payment Unsuccessful' mod='btipay'}</h3>
		<p>{l s='We were unable to process your payment. Please see the details below for more information.' mod='btipay'}</p>
		<div class="alert alert-danger">
			<ul>
				{foreach from=$errors item='error'}
					<li>{$error|escape:'htmlall':'UTF-8'}.</li>
				{/foreach}
			</ul>
		</div>
		{if $order_id and $payment_link}
			<div class="retry-payment">
				<a href="{$payment_link|escape:'htmlall':'UTF-8'}" class="btn btn-primary">
					{l s='Click Here to Retry Payment' mod='btipay'}
				</a>
			</div>
		{else}
			<p>{l s='If you continue to encounter problems, please contact our support team.' mod='btipay'}</p>
		{/if}
	</div>
	<style>
		.content-container {
			margin: 20px;
		}
		.retry-payment {
			margin-top: 20px;
		}
		.alert {
			margin-top: 10px;
		}
	</style>
{/block}

