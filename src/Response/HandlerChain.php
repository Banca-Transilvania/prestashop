<?php

namespace BTiPay\Response;

use BTiPay\Factory\PSTMapFactory;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

class HandlerChain implements HandlerInterface
{
    /**
     * @var HandlerInterface[]
     */
    private $handlers;

    public function __construct(PSTMapFactory $tmapFactory, array $handlers = [])
    {
        $this->handlers = $tmapFactory->create(HandlerInterface::class, $handlers);
    }

    public function handle(array $handlingSubject, ResponseModelInterface $response): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($handlingSubject, $response);
        }
    }

}