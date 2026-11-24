/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

// Creditcards on Checkout
document.addEventListener('change', function(event) {
    const target = event.target;

    if (target.id === 'bt_ipay_use_new_card') {
        const cardList = document.querySelector('.bt-ipay-card-list');
        const saveCardRadio = document.querySelector('.bt-save-card-radio');
        const hidden = document.getElementById('bt_ipay_use_new_card_hidden');

        if (cardList) {
            cardList.style.display = target.checked ? 'none' : 'block';
        }
        if (saveCardRadio) {
            saveCardRadio.style.display = target.checked ? 'block' : 'none';
        }
        if (hidden) {
            hidden.value = target.checked ? 'yes' : 'no';
        }
    }

    if (target.id === 'bt_ipay_save_cards') {
        const hidden = document.getElementById('bt_ipay_save_cards_hidden');
        if (hidden) {
            hidden.value = target.checked ? 'save' : 'no';
        }
    }
});

