<?php

namespace BTiPay\Command;

use BTiPay\Client\ClientInterface;
use BTiPay\Config\BTiPayConfig;
use BTiPay\Exception\CommandException;
use BTiPay\Request\BuilderInterface;
use BTiPay\Response\HandlerInterface;
use BTiPay\Validator\ValidatorInterface;
use BTransilvania\Api\Model\Response\ResponseModelInterface;
use Psr\Log\LoggerInterface;

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
        ValidatorInterface $validator = null,
        HandlerInterface $handler = null
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
     * @return mixed
     * @throws CommandException
     */
    protected function processErrors(ResponseModelInterface $response)
    {
       throw new CommandException($response->getErrorCode() . ': ' . $response->getErrorMessage());
    }
}