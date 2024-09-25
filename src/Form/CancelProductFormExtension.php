<?php

namespace BTiPay\Form;

use PrestaShopBundle\Form\Admin\Sell\Order\CancelProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class CancelProductFormExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CancelProductType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('send_refund_request_btipay', CheckboxType::class, [
            'required' => false,
            'label'    => 'Send Refund Request to BTiPay',
            'attr'     => [
                'material_design' => true,
                'class'           => 'send-refund-request-btipay'
            ],
            'data' => true,
            'row_attr' => [
                'class' => 'form-group send-refund-request-wrapper'
            ],
        ]);
    }
}