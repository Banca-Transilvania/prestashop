<?php

namespace BTransilvania\Tests\Action;

use PHPUnit\Framework\TestCase;
use BTransilvania\Api\Action\ActionFacade;
use BTransilvania\Api\Client\ClientInterface;
use BTransilvania\Api\Exception\ApiException;
use BTransilvania\Api\Model\Request\RequestModelInterface;
use BTransilvania\Api\Model\Response\ResponseModelInterface;

class ActionFacadeTest extends TestCase
{
    public function testExecuteSuccessfully()
    {
        $endpoint = 'testEndpoint';
        $requestData = ['dummy' => 'data'];
        $dummyResponse = new \stdClass();

        $clientMock = $this->createMock(ClientInterface::class);
        $requestModelMock = $this->createMock(RequestModelInterface::class);
        $responseModelMock = $this->createMock(ResponseModelInterface::class);

        $requestModelMock->expects($this->once())
            ->method('buildRequest')
            ->willReturn($requestData);

        $clientMock->expects($this->once())
            ->method('sendRequest')
            ->with($this->equalTo($endpoint), $this->equalTo($requestData))
            ->willReturn($dummyResponse);

        $responseModelMock->expects($this->once())
            ->method('setResponse')
            ->with($this->equalTo($dummyResponse));

        $actionFacade = new ActionFacade($endpoint, $clientMock, $requestModelMock, $responseModelMock);

        $result = $actionFacade->execute($requestData);

        $this->assertSame($responseModelMock, $result);
    }

    public function testExecuteWithException()
    {
        $endpoint = 'testEndpoint';
        $requestData = ['dummy' => 'data'];
        $exceptionMessage = 'Test exception';

        $clientMock = $this->createMock(ClientInterface::class);
        $requestModelMock = $this->createMock(RequestModelInterface::class);

        $requestModelMock->method('buildRequest')->willReturn($requestData);
        $clientMock->method('sendRequest')->willThrowException(new \Exception($exceptionMessage));

        $actionFacade = new ActionFacade($endpoint, $clientMock, $requestModelMock, $this->createMock(ResponseModelInterface::class));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage("Failed to execute API call: " . $exceptionMessage);

        $actionFacade->execute($requestData);
    }
}