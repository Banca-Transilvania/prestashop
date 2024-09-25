<?php

namespace BTransilvania\Tests\Client;

use PHPUnit\Framework\TestCase;
use BTransilvania\Api\Client\Client;
use BTransilvania\Api\Config\Config;
use BTransilvania\Api\HttpAdapter\HttpClientInterface;

class ClientTest extends TestCase
{
    private $configMock;
    private $httpClientMock;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Mocking Config
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('user')->willReturn('testUser');
        $this->configMock->method('password')->willReturn('testPass');
        $this->configMock->method('returnURL')->willReturn('https://return.url');
        $this->configMock->method('environment')->willReturn(Config::TEST_MODE);

        // Mocking HttpClientInterface
        $this->httpClientMock = $this->createMock(HttpClientInterface::class);

        // Initializing Client with mocked dependencies
        $this->client = new Client($this->configMock, $this->httpClientMock);
    }

    public function testSendRequestSuccess()
    {
        $action = 'someAction.do';
        $data = ['additional' => 'data'];

        $expectedResponse = new \stdClass();
        $expectedResponse->success = true;

        $this->httpClientMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('POST'),
                $this->stringContains(Client::IPAY_TEST_URL . $action),
                $this->equalTo(['Content-Type' => 'application/x-www-form-urlencoded']),
                $this->anything()
            )
            ->willReturn($expectedResponse);

        $response = $this->client->sendRequest($action, $data);

        $this->assertEquals($expectedResponse, $response);
    }

    public function testSendRequestErrorHandling()
    {
        $action = 'errorAction.do';
        $data = ['problem' => 'bigProblem'];

        $this->httpClientMock->expects($this->once())
            ->method('send')
            ->willThrowException(new \Exception("Something went wrong"));

        $this->expectException(\BTransilvania\Api\Exception\ApiException::class);
        $this->expectExceptionMessage("Failed to send request: Something went wrong");

        $this->client->sendRequest($action, $data);
    }

    public function testBaseUrlSelectionTestEnvironment()
    {
        $this->configMock->method('environment')->willReturn(Config::TEST_MODE);

        $this->client = new Client($this->configMock, $this->httpClientMock);

        $this->httpClientMock->method('send')
            ->willReturnCallback(function ($method, $url) {
                $this->assertStringStartsWith(Client::IPAY_TEST_URL, $url);
                return new \stdClass(); // Mocked response object
            });

        $this->client->sendRequest('dummyAction.do', []);
    }

    public function testBaseUrlSelectionProdEnvironment()
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('user')->willReturn('testUser');
        $this->configMock->method('password')->willReturn('testPass');
        $this->configMock->method('returnURL')->willReturn('https://return.url');
        $this->configMock->method('environment')->willReturn(Config::PROD_MODE);

        $this->client = new Client($this->configMock, $this->httpClientMock);

        $this->httpClientMock->method('send')
            ->willReturnCallback(function ($method, $url) {
                $this->assertStringStartsWith(Client::IPAY_PROD_URL, $url);
                return new \stdClass(); // Mocked response object
            });

        $this->client->sendRequest('dummyAction.do', []);
    }
}
