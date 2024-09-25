<?php

namespace BTransilvania\Tests\Config;

use PHPUnit\Framework\TestCase;
use BTransilvania\Api\Config\Config;

class ConfigTest extends TestCase
{
    public function testConstructionWithRequiredAttributes()
    {
        $attribs = [
            'user' => 'testUser',
            'password' => 'testPassword'
        ];

        $config = new Config($attribs);

        $this->assertEquals('testUser', $config->user());
        $this->assertEquals('testPassword', $config->password());
    }

    public function testConstructionThrowsExceptionForMissingUser()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("User is required");

        new Config(['password' => 'testPassword']);
    }

    public function testSetterAndGetters()
    {
        $config = new Config(['user' => 'user', 'password' => 'pass']);

        // Test environment setter/getter
        $config->environment(Config::PROD_MODE);
        $this->assertEquals(Config::PROD_MODE, $config->environment());

        // Test currency setter/getter with validation
        $config->currency(Config::EUR_CURRENCY);
        $this->assertEquals(Config::EUR_CURRENCY, $config->currency());
    }

    public function testInvalidEnvironmentThrowsException()
    {
        $config = new Config(['user' => 'user', 'password' => 'pass']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid environment value");

        $config->environment('invalid');
    }

    public function testInvalidCurrencyThrowsException()
    {
        $config = new Config(['user' => 'user', 'password' => 'pass']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid currency value");

        $config->currency(999);
    }

    public function testInvalidReturnUrlThrowsException()
    {
        $config = new Config(['user' => 'user', 'password' => 'pass']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid return URL");

        $config->returnURL('not-a-valid-url');
    }

    public function testLanguageSetterAndGetterWithValidValue()
    {
        $config = new Config(['user' => 'user', 'password' => 'pass']);

        $validLanguage = Config::ENGLISH_LANGUAGE; // Assuming 'en' is a valid language
        $config->language($validLanguage);
        $this->assertEquals($validLanguage, $config->language());
    }

    public function testLanguageSetterWithInvalidValueThrowsException()
    {
        $config = new Config(['user' => 'user', 'password' => 'pass']);

        $invalidLanguage = 'de'; // Assuming 'de' (German) is not a supported language

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid language value: 'de'.");

        $config->language($invalidLanguage);
    }
}
