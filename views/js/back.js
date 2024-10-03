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
document.addEventListener('DOMContentLoaded', function() {
    function toggleFields(testModeEnabled) {
        const testFields = document.querySelectorAll('.form-group.test');
        const liveFields = document.querySelectorAll('.form-group.live');

        testFields.forEach(function(field) {
            field.style.display = testModeEnabled ? '' : 'none';
        });
        liveFields.forEach(function(field) {
            field.style.display = testModeEnabled ? 'none' : '';
        });
    }

    const testModeOn = document.getElementById('BTIPAY_TEST_MODE_on');
    const testModeOff = document.getElementById('BTIPAY_TEST_MODE_off');

    if (testModeOn && testModeOff) {
        toggleFields(testModeOn.checked);

        testModeOn.addEventListener('change', function() {
            if (this.checked) {
                toggleFields(true);
            }
        });

        testModeOff.addEventListener('change', function() {
            if (this.checked) {
                toggleFields(false);
            }
        });
    } else {
        console.error('Test Mode switches not found.');
    }
});

$(document).ready(function() {
    $('.myCustomButtonClass').click(function() {
        $('#captureModal').modal('show');
    });
});


