<?php
/**
 * Copyright (c) 2011, Neuman Vong
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above
 *       copyright notice, this list of conditions and the following
 *       disclaimer in the documentation and/or other materials provided
 *       with the distribution.
 *
 *     * Neither the name of Neuman Vong nor the names of other
 *       contributors may be used to endorse or promote products derived
 *       from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author    Neuman Vong
 * @copyright 2011, Neuman Vong
 * @license   BSD-3-Clause https://opensource.org/licenses/BSD-3-Clause
 */

namespace BTiPay\Webhook;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * JSON Web Token implementation
 *
 * Minimum implementation used by Realtime auth, based on this spec:
 * http://self-issued.info/docs/draft-jones-json-web-token-01.html.
 *
 * @author Neuman Vong
 */
class BTPayJwt
{
    /**
     * @param string $jwt The JWT
     * @param string|null $key The secret key
     * @param bool $verify Don't skip verification process
     *
     * @return object The JWT's payload as a PHP object
     */
    public static function decode($jwt, $key = null, $verify = true)
    {
        $tks = explode('.', $jwt);
        if (count($tks) !== 3) {
            throw new \UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $payloadb64, $cryptob64) = $tks;
        $header = self::jsonDecode(self::urlsafeB64Decode($headb64));
        if (null === $header) {
            throw new \UnexpectedValueException('Invalid segment encoding');
        }
        $payload = self::jsonDecode(self::urlsafeB64Decode($payloadb64));
        if (null === $payload) {
            throw new \UnexpectedValueException('Invalid segment encoding');
        }
        $sig = self::urlsafeB64Decode($cryptob64);
        if ($verify) {
            if (empty($header->alg)) {
                throw new \DomainException('Empty algorithm');
            }
            if (!hash_equals(self::sign("$headb64.$payloadb64", $key, $header->alg), $sig)) {
                throw new \UnexpectedValueException('Signature verification failed');
            }
        }

        return $payload;
    }

    /**
     * @param object|array $payload PHP object or array
     * @param string $key The secret key
     * @param string $algo The signing algorithm
     *
     * @return string A JWT
     */
    public static function encode($payload, $key, $algo = 'HS256')
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $algo,
        ];

        $segments = [];
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($header));
        $segments[] = self::urlsafeB64Encode(self::jsonEncode($payload));
        $signingInput = implode('.', $segments);

        $signature = self::sign($signingInput, $key, $algo);
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * @param string $msg The message to sign
     * @param string $key The secret key
     * @param string $method The signing algorithm
     *
     * @return string An encrypted message
     */
    public static function sign($msg, $key, $method = 'HS256')
    {
        $methods = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];
        if (empty($methods[$method])) {
            throw new \DomainException('Algorithm not supported');
        }

        // phpcs:ignore
        return hash_hmac($methods[$method], $msg, $key, true);
    }

    /**
     * @param string $input JSON string
     *
     * @return object Object representation of JSON string
     */
    public static function jsonDecode($input)
    {
        $obj = json_decode($input);
        if (self::jsonErrorExists()) {
            self::handleJsonError(json_last_error());
        } elseif ($obj === null && $input !== 'null') {
            throw new \DomainException('Null result with non-null input');
        }

        return $obj;
    }

    /**
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (self::jsonErrorExists()) {
            self::handleJsonError(json_last_error());
        } elseif ($json === 'null' && $input !== null) {
            throw new \DomainException('Null result with non-null input');
        }

        return $json;
    }

    private static function jsonErrorExists()
    {
        if (!function_exists('json_last_error')) {
            return false;
        }

        return json_last_error() !== JSON_ERROR_NONE;
    }

    /**
     * @param string $input A base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * @param string $input Anything really
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError($errno)
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
        ];
        throw new \DomainException(isset($messages[$errno]) ? $messages[$errno] : 'Unknown JSON error: ' . $errno);
    }
}
