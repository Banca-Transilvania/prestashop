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

$(document).ready(function () {
    var $amountModal = $('#amountModal');
    var $amountForm = $('#amountForm');
    var $amountInput = $('#amount');
    var $amountError = $('#amountError');
    var $confirmCancelBtn = $('#confirmCancelBtn');

    function showConfirmationModal(callback) {
        $('#confirmationModal').modal('show');
        $confirmCancelBtn.prop('disabled', false);

        $confirmCancelBtn.off('click').on('click', function () {
            $confirmCancelBtn.prop('disabled', true);
            callback();
            $('#confirmationModal').modal('hide');
        });
    }

    function validateAmountInput() {
        var value = $amountInput.val();
        var amount = parseFloat(value);
        if (isNaN(amount) || amount <= 0) {
            return "Please enter a valid amount greater than 0.";
        }

        // Get the action type from the hidden input
        var actionType = $('#action_command').val();
        var maxAmount = (actionType === 'capture') ? $amountInput.data('approved-amount') : $amountInput.data('refund-amount');

        if (amount > maxAmount) {
            return `Amount exceeds the maximum allowed limit of ${maxAmount.toFixed(2)}.`;
        }

        // Allow up to two decimal places
        var decimalPlaces = value.split('.')[1];
        if (decimalPlaces && decimalPlaces.length > 2) {
            return "Please enter no more than two decimal places.";
        }

        return null; // No errors
    }

    function showError(message) {
        $amountError.text(message).fadeIn();

        setTimeout(function() {
            $amountError.fadeOut();
        }, 3000); // Adjust the duration as needed
    }

    $('.bt-button').click(function () {
        const action = $(this).data('action-command');
        const url = $('#apiUrls').data(action + '-url');

        const approvedAmount = $amountInput.data('approved-amount');

        if (action === 'cancel') {
            showConfirmationModal(function () {
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    success: function (response) {
                        console.log('Cancellation request successful.');
                        window.location.reload();
                    },
                    error: function (xhr, status, error) {
                        console.error('Cancellation request failed:', error);
                        alert('Cancellation request failed: ' + error);
                    }
                });
            });

            return false;
        }

        $amountForm.attr('action', url);
        $('#action_command').val(action);

        if (action === 'capture' || action === 'cancel') {
            $amountInput.val(approvedAmount);
        }

        $amountModal.modal('show');
    });

    $('#amount').on('input', function () {
        $(this).val($(this).val().replace(/[^0-9.]/g, '')
            .split('.').reduce(function (acc, part, index) {
                return index === 0 ? acc + part : acc + (index === 1 ? '.' + part : part);
            }, '')
            .replace(/^0+/, '0')
            .replace(/(\.\d{2})\d+/, '$1'));

        if ($(this).val().startsWith('.')) {
            $(this).val('0' + $(this).val());
        }
    });


    $amountForm.submit(function (e) {
        e.preventDefault();

        var validationMessage = validateAmountInput();
        if (validationMessage !== null) {
            showError(validationMessage);
            return;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                $amountModal.modal('hide');
                window.location.reload();
            },
            error: function (xhr) {
                let errorMsg = 'Error performing operation.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg += ' ' + xhr.responseJSON.message;
                }
                showError(errorMsg);
            }
        });
    });
});


$(document).ready(function() {
    var $targetContainer = $('.refund-checkboxes-container');
    var $checkboxFormGroup = $('#cancel_product_send_refund_request_btipay').closest('.form-group');
    if ($checkboxFormGroup.length && $targetContainer.length) {
        $checkboxFormGroup.appendTo($targetContainer);
        $checkboxFormGroup.find('.form-control-label').remove();
    }
});