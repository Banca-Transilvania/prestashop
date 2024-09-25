<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminBTiPayCaptureController extends ModuleAdminController
{
    public function initContent()
    {
        parent::initContent();
        $this->layout = 'content.tpl';

        $this->context->smarty->assign(array(
            'content' => $this->renderModal()
        ));
    }

    /**
     * @return void
     *
     * @throws SmartyException
     */
    public function initModal(): void
    {
        parent::initModal();

        $orderId = \Tools::getValue('id_order');
        if (!$orderId) {
            $orderId = 10;  // Set a default or handle the error appropriately
        }

        $this->context->smarty->assign(array(
            'id_order' => $orderId
        ));

        $modalContent = $this->context->smarty->fetch($this->module->getLocalPath() . 'views/templates/admin/captureModal.tpl');

        $this->modals[] = [
            'modal_id' => 'btipayCaptureModal',
            'modal_class' => 'modal-lg',
            'modal_title' => $this->module->l('Capture Payment for BT iPay'),
            'modal_content' => $modalContent,
            'modal_cancel_label' => $this->trans('Cancel', [], 'Admin.Actions'),
            'modal_actions' => [
                [
                    'type' => 'button',
                    'label' => $this->module->l('Confirm Capture'),
                    'class' => 'btn-primary',
                    'value' => 'capture'
                ],
                [
                    'type' => 'link',
                    'label' => $this->module->l('View Details'),
                    'class' => 'btn-link',
                    'href' => $this->context->link->getAdminLink('AdminOrders') . '&viewOrder&id_order=' . \Tools::getValue('id_order')
                ]
            ],
        ];
    }
}