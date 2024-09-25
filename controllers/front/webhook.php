<?php

use BTiPay\Webhook\BTPayJwt;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BtipayWebhookModuleFrontController extends \ModuleFrontControllerCore
{
    private const FILE_NAME = 'webhook';

    /** @var Btipay */
    public $module;
    /** @var bool */
    public $ssl = true;
    /** @var bool */
    public $display_column_left = false;
    /** @var bool */
    public $display_column_right = false;

    public function initContent(): void
    {
        /** @var \BTiPay\Webhook\WebhookService $webhookService */
        $webhookService = $this->get('btipay.webhook.service');
        /** @var \Monolog\Logger $logger */
        $logger = $this->get('btipay.logger');
        /** @var \BTiPay\Config\BTiPayConfig $config */
        $config = $this->get('btipay.config');

        $logger->info(sprintf('%s - Controller called', self::FILE_NAME));
        try {
            $jwt = $this->decode($config->getCallbackKey());
            $payload = $this->getPayload($jwt);

            // Log the payload
            $logger->info(sprintf('%s - Payload: %s', self::FILE_NAME, json_encode($payload)));

            $webhookService->executeWebhook($payload);
            $logger->info(sprintf('%s - Controller action ended', self::FILE_NAME));
            $this->ajaxRender($this->createJsonResponse(['success' => true], 200));
        } catch (\Throwable $exception) {
            $logger->error('Failed to handle webhook', [
                'Exception message' => $exception->getMessage(),
                'Exception code'    => $exception->getCode(),
            ]);
            $this->ajaxRender($this->createJsonResponse(['error' => $exception->getMessage()], 400));
        }

        exit;
    }

    private function decode($callbackKey)
    {
        return BTPayJwt::decode(
            file_get_contents('php://input'),
            BTPayJwt::urlsafeB64Decode(
                $callbackKey
            ), true
        );
    }

    private function getPayload(\stdClass $jwt)
    {
        if (
            property_exists($jwt, 'payload') &&
            $jwt->payload instanceof \stdClass
        ) {
            return $jwt->payload;
        }
        throw new \Exception('Cannot find jwt payload');
    }

    private function createJsonResponse(array $data, int $statusCode): string
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    /**
     * Prevent displaying the maintenance page.
     *
     * @return void
     */
    protected function displayMaintenancePage()
    {
    }
}
