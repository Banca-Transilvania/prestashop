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
<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module" id="btipay_payment_button">
			{if $cart->getOrderTotal() < 2}
				<a href="">
					<img src="{$domain|cat:$payment_button|escape:'html':'UTF-8'}" alt="{l s='Pay with my payment module' mod='btipay'}" />
					{l s='Minimum amount required in order to pay with my payment module:' mod='btipay'} {convertPrice price=2}
				</a>
			{else}
				<a href="{$link->getModuleLink('btipay', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with my payment module' mod='btipay'}">
					<img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.png" alt="{l s='Pay with my payment module' mod='btipay'}" width="32" height="32" />
					{l s='Pay with my payment module' mod='btipay'}
				</a>
			{/if}
		</p>
	</div>
</div>
