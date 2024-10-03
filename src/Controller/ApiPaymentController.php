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

namespace BTiPay\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BTiPay\Service\CaptureService;
use BTiPay\Service\RefundService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPaymentController extends FrameworkBundleAdminController
{
    public function handleRequest(Request $request, $action, $orderId)
    {
        $amount = $request->request->get('amount') ?? null;

        if (!is_numeric($orderId)) {
            return $this->addFlashError('Invalid value for `orderId`');
        }

        $type = 'btipay_api_payment_handle';

        $data['order'] = new \Order($orderId);

        switch ($action) {
            case 'capture':
                return $this->handleCapture($data, $type, $amount);
            case 'refund':
                return $this->handleRefund($data, $type, $amount);
            case 'cancel':
                return $this->handleCancel($data, $type, $amount);
            default:
                return new JsonResponse(['error' => 'Unknown action'], Response::HTTP_BAD_REQUEST);
        }
    }

    private function handleCapture($data, $type, $amount)
    {
        try {
            /** @var CaptureService $captureService */
            $captureService = $this->get('btipay.capture.service');
            $captureService->execute($data, $type, $amount);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return $this->addFlashError($e->getMessage());
        }
    }

    private function handleRefund($data, $type, $amount)
    {
        try {
            /** @var RefundService $refundService */
            $refundService = $this->get('btipay.refund.service');
            $result = $refundService->customRefund($data, $type, $amount);

            return new JsonResponse(['success' => true, 'message' => $result]);
        } catch (\Exception $e) {
            return $this->addFlashError($e->getMessage());
        }
    }

    private function handleCancel($data, $type, $amount)
    {
        try {
            $cancelService = $this->get('btipay.cancel.service');
            $result = $cancelService->execute($data, $type, $amount);

            return new JsonResponse(['success' => true, 'message' => $result]);
        } catch (\Exception $e) {
            return $this->addFlashError($e->getMessage());
        }
    }

    private function addFlashError($message)
    {
        $this->addFlash('error', $message);

        return new JsonResponse(['error' => true, 'message' => $message], Response::HTTP_BAD_REQUEST);
    }
}
