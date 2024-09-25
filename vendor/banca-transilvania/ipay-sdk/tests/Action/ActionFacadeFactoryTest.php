<?php

namespace BTransilvania\Tests\Action;

use PHPUnit\Framework\TestCase;
use BTransilvania\Api\Action\ActionFacadeFactory;
use BTransilvania\Api\Client\ClientInterface;

class ActionFacadeFactoryTest extends TestCase
{
    private $clientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientMock = $this->createMock(ClientInterface::class);
    }

    /**
     * @dataProvider actionProvider
     */
    public function testCreateActionFacade(string $action, string $expectedEndpoint, string $expectedRequestModelClass, string $expectedResponseModelClass)
    {
        $data = []; // Assuming data is an empty array for simplicity

        $actionFacade = ActionFacadeFactory::createActionFacade($action, $this->clientMock, $data);

        $reflection = new \ReflectionClass($actionFacade);
        $endpointProperty = $reflection->getProperty('endpoint');
        $endpointProperty->setAccessible(true);

        $requestModelProperty = $reflection->getProperty('requestModel');
        $requestModelProperty->setAccessible(true);

        $responseModelProperty = $reflection->getProperty('responseModel');
        $responseModelProperty->setAccessible(true);

        $this->assertEquals($expectedEndpoint, $endpointProperty->getValue($actionFacade));
        $this->assertInstanceOf($expectedRequestModelClass, $requestModelProperty->getValue($actionFacade));
        $this->assertInstanceOf($expectedResponseModelClass, $responseModelProperty->getValue($actionFacade));
    }

    public function testCreateActionFacadeWithInvalidAction()
    {
        $invalidAction = 'invalidAction';
        $expectedExceptionMessage = "Unknown action: $invalidAction";

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        ActionFacadeFactory::createActionFacade('invalidAction', $this->clientMock, []);
    }

    public function actionProvider(): array
    {
        return [
            ['register', 'register.do', \BTransilvania\Api\Model\Request\RegisterRequestModel::class, \BTransilvania\Api\Model\Response\RegisterResponseModel::class],
            ['registerPreAuth', 'registerPreAuth.do', \BTransilvania\Api\Model\Request\RegisterRequestModel::class, \BTransilvania\Api\Model\Response\RegisterResponseModel::class],
            ['deposit', 'deposit.do', \BTransilvania\Api\Model\Request\DepositModel::class, \BTransilvania\Api\Model\Response\DepositResponse::class],
            ['reverse', 'reverse.do', \BTransilvania\Api\Model\Request\ReverseModel::class, \BTransilvania\Api\Model\Response\RefundResponse::class],
            ['refund', 'refund.do', \BTransilvania\Api\Model\Request\RefundModel::class, \BTransilvania\Api\Model\Response\RefundResponse::class],
            ['getOrderStatusExtended', 'getOrderStatusExtended.do', \BTransilvania\Api\Model\Request\GerOrderStatusModel::class, \BTransilvania\Api\Model\Response\GetOrderStatusResponseModel::class],
            ['getFinishedPaymentInfo', 'getFinishedPaymentInfo.do', \BTransilvania\Api\Model\Request\GetFinishedPaymentModel::class, \BTransilvania\Api\Model\Response\GetFinishedPaymentResponseModel::class],
            ['paymentOrderBinding', 'paymentOrderBinding.do', \BTransilvania\Api\Model\Request\PaymentOrderBindingModel::class, \BTransilvania\Api\Model\Response\PaymentOrderBindingResponseModel::class],
            ['getBindings', 'getBindings.do', \BTransilvania\Api\Model\Request\GetBindingsModel::class, \BTransilvania\Api\Model\Response\GetBindingsResponseModel::class],
            ['unBindCard', 'unBindCard.do', \BTransilvania\Api\Model\Request\BindCardModel::class, \BTransilvania\Api\Model\Response\BindCardResponseModel::class],
            ['bindCard', 'bindCard.do', \BTransilvania\Api\Model\Request\BindCardModel::class, \BTransilvania\Api\Model\Response\BindCardResponseModel::class],
        ];
    }
}
