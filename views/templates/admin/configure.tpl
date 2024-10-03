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
<div class="panel">
	<ul class="nav nav-tabs" role="tablist">
		<li class="nav-item {if $selectedTab=='generalSettings'}active{/if}">
			<a class="nav-link {if $selectedTab=='generalSettings'}active{/if}" id="general-settings-tab" data-toggle="tab"
			   href="#generalSettings" role="tab"
			   aria-controls="generalSettings"
			   aria-selected="false"><i class="icon-cogs"></i> {l s='General Settings' mod='btipay'}</a>
		</li>

		<li class="nav-item {if $selectedTab=='paymentMethod'}active{/if}">
			<a class="nav-link {if $selectedTab=='paymentMethod'}active{/if}" id="payment-settings-tab" data-toggle="tab"
			   href="#paymentMethod" role="tab"
			   aria-controls="generalSettings"
			   aria-selected="false"><i class="icon-cogs"></i> {l s='Payment Method Settings' mod='btipay'}</a>
		</li>
	</ul>
	<div class="tab-content">
		<div class="panel tab-pane fade {if $selectedTab == 'generalSettings'}active in{/if}" id="generalSettings" role="tabpanel" aria-labelledby="general-settings-tab">
			{include file='./general_info.tpl'}
			{$generalForm}
		</div>
		<div class="panel tab-pane fade {if $selectedTab == 'paymentMethod'}active in{/if}" id="paymentMethod" role="tabpanel" aria-labelledby="payment-settings-tab">
			{$paymentform}
		</div>
	</div>
</div>


