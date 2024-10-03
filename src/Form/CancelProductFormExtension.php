<?php
/**
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
 */

namespace BTiPay\Form;

use PrestaShopBundle\Form\Admin\Sell\Order\CancelProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
            'label' => 'Send Refund Request to BTiPay',
            'attr' => [
                'material_design' => true,
                'class' => 'send-refund-request-btipay',
            ],
            'data' => true,
            'row_attr' => [
                'class' => 'form-group send-refund-request-wrapper',
            ],
        ]);
    }
}
