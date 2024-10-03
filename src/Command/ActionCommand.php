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

namespace BTiPay\Command;

use BTiPay\Client\ClientInterface;
use BTiPay\Config\BTiPayConfig;
use BTiPay\Exception\CommandException;
use BTiPay\Request\BuilderInterface;
use BTiPay\Response\HandlerInterface;
use BTiPay\Validator\ValidatorInterface;
use BTransilvania\Api\Model\Response\ResponseModelInterface;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ActionCommand implements CommandInterface
{
    protected string $action;
    protected BuilderInterface $requestBuilder;
    protected ClientInterface $client;
    protected ?HandlerInterface $handler;
    protected ?ValidatorInterface $validator;
    protected LoggerInterface $logger;
    protected BTiPayConfig $btPayConfig;

    public function __construct(
        string $action,
        BuilderInterface $requestBuilder,
        ClientInterface $client,
        LoggerInterface $logger,
        BTiPayConfig $btPayConfig,
        ?ValidatorInterface $validator = null,
        ?HandlerInterface $handler = null,
    ) {
        $this->action = $action;
        $this->requestBuilder = $requestBuilder;
        $this->client = $client;
        $this->validator = $validator;
        $this->handler = $handler;
        $this->logger = $logger;
        $this->btPayConfig = $btPayConfig;
    }

    /**
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        $commandSubject['btPayConfig'] = $this->btPayConfig;
        $request = $this->requestBuilder->build($commandSubject);

        $this->logger->debug('');
        $response = $this->client->placeRequest($this->action, $request);

        if ($this->validator !== null) {
            $result = $this->validator->validate(array_merge($commandSubject, ['response' => $response]));
            if (!$result) {
                $this->processErrors($response);
            }
        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }

        return $response;
    }

    /**
     * @param ResponseModelInterface $response
     *
     * @return mixed
     *
     * @throws CommandException
     */
    protected function processErrors(ResponseModelInterface $response)
    {
        throw new CommandException($response->getErrorCode() . ': ' . $response->getErrorMessage());
    }
}
