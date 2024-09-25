<?php

namespace BTransilvania\Tests\Model\Request;

use BTransilvania\Api\Model\Request\OrderBundleModel;
use PHPUnit\Framework\TestCase;
use BTransilvania\Api\Model\Request\RegisterRequestModel;

class RegisterRequestModelTest extends TestCase
{
    public function testPropertySettingThroughConstructor()
    {
        $data = $this->getRegisterData();

        $model = new RegisterRequestModel($data);

        foreach ($data as $property => $expectedValue) {
            if ($property === 'orderBundle') {
                $this->assertInstanceOf(OrderBundleModel::class, $model->$property);
                continue;
            }
            $this->assertEquals($expectedValue, $model->$property);
        }
    }

    public function testBuildRequestWithCurrencyConversion()
    {
        $data = $this->getRegisterData();

        $data['currency'] = 'EUR';

        $model = new RegisterRequestModel($data);

        $requestArray = $model->buildRequest();

        // Verify the currency conversion logic and overall request structure
        $this->assertIsNumeric($requestArray['currency']);
        $this->assertEquals(978, $requestArray['currency']); // Assuming 'EUR' converts to 978
    }

    public function testOrderBundleModelPropertiesAreCorrectlySet()
    {
        $data = $this->getRegisterData();
        $model = new RegisterRequestModel($data);
        $orderBundle = $model->orderBundle;

        $expectedCustomerDetails = $data['orderBundle']['customerDetails'];
        $actualCustomerDetails = $orderBundle->customerDetails;

        $this->assertEquals($expectedCustomerDetails['email'], $actualCustomerDetails->email);
        $this->assertEquals($expectedCustomerDetails['phone'], $actualCustomerDetails->phone);
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