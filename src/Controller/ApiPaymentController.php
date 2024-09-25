<?php

namespace BTiPay\Controller;

if (!defined('_PS_VERSION_')) {
    exit;
}

use BTiPay\Service\CaptureService;
use BTiPay\Service\RefundService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

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
