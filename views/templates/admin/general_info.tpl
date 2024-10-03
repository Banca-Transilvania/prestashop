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
	<div class="row btipay-header">
		<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.jpg" class="col-xs-6 col-md-3 text-center" id="payment-logo" />
		<div class="col-xs-6 col-md-6 text-center">
			<h3>{l s='Effortlessly manage payments with BT iPay’s user-friendly interface.' mod='btipay'}</h3>
			<p class="text-muted">
				{l s='Our module supports all transaction types, ensuring secure and efficient payment processing.' mod='btipay'}
			</p>
			<p class="small">
				{l s='Developed by Arnia Software Romania and backed by Banca Transilvania’s robust online payment solution, this module is designed to streamline your online payment process, offering a secure and efficient transaction experience for both merchants and customers.' mod='btipay'}
			</p>
		</div>
		<div class="col-xs-12 col-md-3 text-center">
			<a href="https://btepos.ro/go/cerere#stayhere" class="btn btn-primary" id="create-account-btn">{l s='Create an account' mod='btipay'}</a><br />
			{l s='Already have one?' mod='btipay'}<a href="https://ecclients.btrl.ro/console/index.html#login"> {l s='Log in' mod='btipay'}</a>
		</div>
	</div>

	<hr />

	<div class="btipay-content">
		<div class="row">
			<div class="col-md-4">
				<h5>{l s='Benefits of using BT iPay payment module' mod='btipay'}</h5>
				<ul class="ul-spaced">
					<li>
						<strong>{l s='Configurability' mod='btipay'}:</strong>
						{l s='Flexible configuration options with easy toggle between test and live modes.' mod='btipay'}
					</li>

					<li>
						<strong>{l s='Multi-Currency Support' mod='btipay'}:</strong>
						{l s='Multi-currency support with comprehensive logging features for easier troubleshooting.' mod='btipay'}
					</li>

					<li>
						<strong>{l s='Streamlined Transactions' mod='btipay'}:</strong>
						{l s='Card on File functionality to streamline future transactions.' mod='btipay'}
					</li>

					<li>
						<strong>{l s='Efficient Invoicing' mod='btipay'}:</strong>
						{l s='Automatic invoice generation upon successful payment, customizable per merchant preference.' mod='btipay'}
					</li>

					<li>
						<strong>{l s='Internationalization' mod='btipay'}:</strong>
						{l s='Full internationalization support for English and Romanian, with easily translatable module texts for additional languages.' mod='btipay'}
					</li>
				</ul>
			</div>

			<div class="col-md-3">
				<h5>{l s='Module Specifications' mod='btipay'}</h5>
				<dl class="list-unstyled">
					<dt>{l s='Release Date' mod='btipay'}</dt>
					<dd>{l s='Mai 2024' mod='btipay'}</dd>
					<dt>{l s='Version' mod='btipay'}</dt>
					<dd>{l s='1.0.0' mod='btipay'}</dd>
					<dt>{l s='Supported PHP Versions' mod='btipay'}</dt>
					<dd>{l s='7.4, 8.0+' mod='btipay'}</dd>
					<dt>{l s='Compatible with PrestaShop' mod='btipay'}</dt>
					<dd>{l s='1.7.x and 8.1' mod='btipay'}</dd>
				</dl>
			</div>
		</div>

		<hr />


		<div class="row">
			<div class="row">
				<div class="col-md-12">
					<p class="text-muted">{l s='Online transactions through BT iPay using AMEX cards are not supported, however, we accept payments with Revolut.' mod='btipay'}</p>
				</div>
			</div>
			<div class="col-md-3">
				<img src="{$module_dir|escape:'html':'UTF-8'}views/img/cards.png" id="payment-logo" />

			</div>
			<div class="col-md-9 text-center">
				<h6>{l s='For more information, call 0264.597.710' mod='btipay'} {l s='or' mod='btipay'} <a href="mailto:contact@btepos.com">contact@btepos.com</a></h6>
			</div>

		</div>

	</div>
</div>

<div class="panel">
	<p class="text-muted">
		<i class="icon icon-info-circle"></i>
		{l s='Access credentials are username and password pairs provided by Banca Transilvania when the merchant is created on the iPay platform. These are necessary for accessing the iPay console and for calling the APIs.' mod='btipay'}
	</p>
	<p class="text-muted">
		<i class="icon icon-stop"></i>
		{l s='Attention! Production credentials do not work on the test environment or vice versa.' mod='btipay'}
	</p>

	<p>
		<a href="https://btepos.ro/module-ecommerce"><i class="icon icon-file"></i> {l s='Link to the documentation' mod='btipay'}</a>
	</p>
</div>

