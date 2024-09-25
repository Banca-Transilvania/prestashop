<?php

namespace BTiPay\Helper;

use Configuration;

class Encrypt
{
    public const TO_ENCRYPT = [
        'expiration',
        'cardholderName',
        'pan'
    ];

    public const ALG = "AES-256-GCM";
    private const STATIC_SALT = 'hoP%687,f:q£';

    private static $key;

    private static function encrypt($data)
    {
        self::getKey();
        $encryptionKey = base64_decode(self::$key);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ALG));
        $encrypted = openssl_encrypt($data, self::ALG, $encryptionKey, 0, $iv, $tag);
        if ($encrypted === false) {
            throw new \Exception(openssl_error_string());
        }
        return base64_encode($encrypted . '::' . $iv . '::' . $tag);
    }

    private static function decrypt($data)
    {
        self::getKey();
        $encryptionKey = base64_decode(self::$key);
        list($encryptedData, $iv, $tag) = explode('::', base64_decode($data), 3);
        $decrypted = openssl_decrypt($encryptedData, self::ALG, $encryptionKey, 0, $iv, $tag);
        if ($decrypted === false) {
            throw new \Exception(openssl_error_string());
        }
        return $decrypted;
    }

    private static function getKey()
    {
        $keyFile = _PS_ROOT_DIR_ . '/app/config/ipay_encryption_key';

        if (!self::$key) {
            if (file_exists($keyFile)) {
                $key = trim(file_get_contents($keyFile));
                if ($key) {
                    self::$key = substr(hash('sha256', self::STATIC_SALT, true), 0, 32);
                }
            } else {
                $newKey = bin2hex(openssl_random_pseudo_bytes(32));
                file_put_contents($keyFile, $newKey);
                self::$key = substr(hash('sha256', self::STATIC_SALT . $newKey, true), 0, 32);
            }
        }
    }


    /**
     * @throws \Exception
     */
    public static function encryptCard(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::TO_ENCRYPT)) {
                $data[$key] = self::encrypt($value);
            }
        }
        return $data;
    }

    /**
     * @throws \Exception
     */
    public static function decryptCard(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::TO_ENCRYPT)) {
                $data[$key] = self::decrypt($value);
            }
        }
        return $data;
    }

    /**
     * Encrypts a single configuration value.
     *
     * @param string $value
     * @return string
     * @throws \Exception
     */
    public static function encryptConfigValue(string $value): string
    {
        return self::encrypt($value);
    }

    /**
     * Decrypts a single configuration value.
     *
     * @param string $value
     * @return string
     * @throws \Exception
     */
    public static function decryptConfigValue(string $value): string
    {
        return !empty($value) ? self::decrypt($value) : $value;
    }
}