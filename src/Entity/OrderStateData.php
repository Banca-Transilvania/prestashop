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

namespace BTiPay\Entity;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderStateData
{
    /** @var string */
    private $name;
    /** @var bool */
    private $sendEmail;
    /** @var string */
    private $color;
    /** @var bool */
    private $logable;
    /** @var bool */
    private $delivery;
    /** @var bool */
    private $invoice;
    /** @var bool */
    private $shipped;
    /** @var bool */
    private $paid;
    /** @var string */
    private $template;
    /** @var bool */
    private $pdfInvoice;

    public function __construct(
        string $name,
        string $color,
        bool $sendEmail = false,
        bool $logable = false,
        bool $delivery = false,
        bool $invoice = false,
        bool $shipped = false,
        bool $paid = false,
        string $template = '',
        bool $pdfInvoice = false,
    ) {
        $this->name = $name;
        $this->sendEmail = $sendEmail;
        $this->color = $color;
        $this->logable = $logable;
        $this->delivery = $delivery;
        $this->invoice = $invoice;
        $this->shipped = $shipped;
        $this->paid = $paid;
        $this->template = $template;
        $this->pdfInvoice = $pdfInvoice;
    }

    public function getModuleName(): string
    {
        return 'btipay';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isSendEmail(): bool
    {
        return $this->sendEmail;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isLogable(): bool
    {
        return $this->logable;
    }

    public function isDelivery(): bool
    {
        return $this->delivery;
    }

    public function isInvoice(): bool
    {
        return $this->invoice;
    }

    public function isShipped(): bool
    {
        return $this->shipped;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function isPdfInvoice(): bool
    {
        return $this->pdfInvoice;
    }
}
