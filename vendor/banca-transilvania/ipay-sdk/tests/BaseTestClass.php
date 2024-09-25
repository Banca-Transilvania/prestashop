<?php

namespace BTransilvania\Tests;

use PHPUnit\Framework\TestCase;
use BTransilvania\Api\IPayClient;
use BTransilvania\Api\HttpAdapter\HttpClientInterface;

class BaseTestClass extends TestCase
{
    protected $config;
    protected $httpClient;
    protected $ipayClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = [
            'user'         => '*****', // Replace '*****' with your actual iPay user.
            'password'     => '*****', // Replace '*****' with your actual iPay password.
            'environment'  => 'test',
            'platformName' => 'Magento - Community',
            'language'     => 'en',
            'returnUrl'    => 'https://magazinulmeu.ro/finish.html'
        ];

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->ipayClient = new IPayClient($this->config, $this->httpClient);
    }
}