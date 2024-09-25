<?php

namespace BTransilvania\Tests;

use BTransilvania\Api\HttpAdapter\HttpClientInterface;
use BTransilvania\Api\Model\Response\RegisterResponseModel;
use BTransilvania\Api\Client\Client;
use BTransilvania\Api\Exception\ApiException;
use BTransilvania\Api\IPayClient;

class IPayClientTest extends BaseTestClass
{
    public function testHttpClientInitialization()
    {
        // Case 1: HTTP client not initially set
        $ipayClient1 = new IPayClient($this->config);
        // Simulate an action that triggers ensureHttpClientIsInitialized
        $ipayClient1->register($this->getRegisterData());
        // Assert that an HTTP client has been initialized
        $this->assertNotNull($ipayClient1->getHttpClient());

        // Case 2: HTTP client already set
        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $dummyResponse = new \stdClass;
        $mockHttpClient->method('send')->willReturn($dummyResponse);
        $ipayClient2 = new IPayClient($this->config, $mockHttpClient);
        $ipayClient2->register($this->getRegisterData());
        // Assert the same HTTP client is still set
        $this->assertSame($mockHttpClient, $ipayClient2->getHttpClient());
    }


    public function testRegister()
    {
        $action = 'register.do';
        $data = $this->getRegisterData();;
        $expectedUrl = Client::IPAY_TEST_URL . $action;

        $responseBody = new \stdClass;
        $responseBody->isSuccess = true;
        $responseBody->hasRedirect = true;
        $responseBody->formUrl = 'https://example.com/redirect';
        $responseBody->orderId = '123456';

        $this->httpClient->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo($expectedUrl),
                $this->equalTo(['Content-Type' => 'application/x-www-form-urlencoded']),
                $this->anything()
            )
            ->willReturn($responseBody);

        $result = $this->ipayClient->register($data);

        $this->assertInstanceOf(RegisterResponseModel::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('https://example.com/redirect', $result->getRedirectURL());
        $this->assertEquals('123456', $result->getOrderId());
    }

    public function testRegisterWithApiException()
    {
        $action = 'register.do';
        $data = $this->getRegisterData();
        $expectedUrl = Client::IPAY_TEST_URL . $action;

        $this->httpClient->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo($expectedUrl),
                $this->equalTo(['Content-Type' => 'application/x-www-form-urlencoded']),
                $this->anything()
            )
            ->willThrowException(new ApiException("Error Communicating with Server"));

        $this->expectException(ApiException::class);

        $result = $this->ipayClient->register($data);
    }

    public function testRegisterWithInvalidResponse()
    {
        $action = 'register.do';
        $data = $this->getRegisterData();
        $expectedUrl = Client::IPAY_TEST_URL . $action;

        $responseBody = new \stdClass;
        // Simulate an invalid response
        $responseBody->errorCode = 'ERROR';
        $responseBody->errorMessage = 'Invalid request';

        $this->httpClient->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo($expectedUrl),
                $this->equalTo(['Content-Type' => 'application/x-www-form-urlencoded']),
                $this->anything()
            )
            ->willReturn($responseBody);

        $result = $this->ipayClient->register($data);

        $this->assertInstanceOf(RegisterResponseModel::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('ERROR', $result->getErrorCode());
        $this->assertEquals('Invalid request', $result->getErrorMessage());
    }

    public function testRegisterWithInvalidCurrencyCode()
    {
        $data = $this->getRegisterData();
        unset($data['currency']); // Simulate missing currency code

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage("Invalid currency code");

        $this->ipayClient->register($data);
    }


    private function getRegisterData(): array
    {
        return [
            'orderNumber' => '209126',
            'amount'      => '1000',
            'currency'    => 'RON',
            'description' => 'testBT',
            'returnUrl'   => 'https://magazinulmeu.ro/finish.html',
            'orderBundle' => [
                'orderCreationDate' => '2020-09-29',
                'customerDetails'   => [
                    'email'        => 'email@test.com',
                    'phone'        => '40740123456',
                    'deliveryInfo' => [
                        'deliveryType' => 'comanda',
                        'country'      => 'Romania',
                        'city'         => 'Cluj',
                        'postAddress'  => 'Str.Sperantei',
                        'postalCode'   => '12345'
                    ],
                    'billingInfo'  => [
                        'deliveryType' => 'comanda',
                        'country'      => '642',
                        'city'         => 'Cluj',
                        'postAddress'  => 'Str.Sperantei',
                        'postAddress2' => 'Str.Speraneti',
                        'postAddress3' => 'Strada',
                        'postalCode'   => '12345'
                    ]
                ]
            ]
        ];
    }
}