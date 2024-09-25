<?php

namespace BTiPay\Tests\Unit;

use BTiPay\Request\OrderBundleRequestBuilder;
use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Adapter\Entity\Address;

class OrderBundleRequestBuilderTest extends TestCase
{
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new OrderBundleRequestBuilder();

        require_once __DIR__ . '/../../../../config/config.inc.php';
        require_once __DIR__ . '/../../../../init.php';
    }

    public function addressProvider()
    {
        return [
            'short_address' => ['123 Main St', '', ['postAddress' => '123 Main St']],
            'long_address_split' => ['1234 Main Street, Apartment 456', 'Suite 789', [
                'postAddress'  => '1234 Main Street, Apartment 456',
                'postAddress2' => 'Suite 789'
            ]],
            'exact_max_length' => [str_repeat('A', 50), '', ['postAddress' => str_repeat('A', 50)]],
            'long_without_space' => [$this->generateAlphabetString(75), '', [
                'postAddress'  => 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWX',
                'postAddress2' => 'YZABCDEFGHIJKLMNOPQRSTUVW'
            ]],
            'multiple_splits' => [
                str_repeat('A', 60) . ' ' . str_repeat('B', 60), 'C' . str_repeat('D', 40),
                [
                    'postAddress'  => str_repeat('A', 50),
                    'postAddress2' => 'AAAAAAAAAA BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
                    'postAddress3' => 'BBBBBBBBBBBBBBBBBBBBB CDDDDDDDDDDDDDDDDDDDDDDDDDDD'
                ]
            ],
            'long_with_spaces' => [
                '1234 Main Street Apartment', '456 Suite 789',
                [
                    'postAddress' => '1234 Main Street Apartment',
                    'postAddress2' => '456 Suite 789'
                ]
            ],
            'long_with_no_spaces' => [
                str_repeat('A', 60), str_repeat('B', 60),
                [
                    'postAddress' => str_repeat('A', 50),
                    'postAddress2' => str_repeat('A', 10) . ' ' . str_repeat('B', 39),
                    'postAddress3' => str_repeat('B', 21)
                ]
            ],
            'empty_address' => ['', '', ['postAddress' => '']]
        ];
    }

    /**
     * @dataProvider addressProvider
     */
    public function testGetAddressChunks($address1, $address2, $expected)
    {
        $addressMock = $this->createMock(Address::class);
        $addressMock->address1 = $address1;
        $addressMock->address2 = $address2;

        $result = $this->invokeMethod($this->builder, 'getAddressChunks', [$addressMock]);

        $this->assertEquals($expected, $result);
    }

    // Helper method to access private/protected methods
    protected function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    private function generateAlphabetString(int $length, int $offset = 0): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $alphabet[($i + $offset) % 26];
        }
        return $str;
    }
}
