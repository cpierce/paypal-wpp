<?php
/**
 * Library for handling Paypal WPP Calls.
 *
 * @copyright Copyright (c) 2016, Chris Pierce
 * @author Chris Pierce <cpierce@csdurant.com>
 *
 * @link http://www.github.com/paypal-wpp
 */

namespace PaypalWPP;

/**
 * PaypalWPP class.
 */
class PaypalWPP
{
    /**
     * Version of Library.
     *
     * @const string
     */
    const VERSION = '53.0';

    /**
     * WPP endpoint for passing data.
     *
     * @var string
     */
    protected $wppEndpoint = 'https://api-3t.paypal.com/nvp';

    /**
     * WPP username for connection.
     *
     * @var string
     */
    protected $wppUsername;

    /**
     * WPP password for connection.
     *
     * @var string
     */
    protected $wppPassword;

    /**
     * WPP signature for connection.
     *
     * @var string
     */
    protected $wppSignature;

    /**
     * Construct Method.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        if (empty($config)) {
            throw new \RuntimeException('No config provided');
        }

        if (!is_array($config)) {
            throw new \RuntimeException('Config payload required');
        }

        if (empty($config['username'])) {
            throw new \RuntimeException('Username is required in payload');
        }

        if (empty($config['password'])) {
            throw new \RuntimeException('Password is required in payload');
        }

        if (empty($config['signature'])) {
            throw new \RuntimeException('Signature is required in payload');
        }

        $this->wppUsername = urlencode($config['username']);
        $this->wppPassword = urlencode($config['password']);
        $this->wppSignature = urlencode($config['signature']);

        if (!empty($config['endpoint'])) {
            $this->wppEndpoint = urlencode($config['endpoint']);
        }
    }

    /**
     * Web Payments Pro Hash Method.
     *
     * @throws \RuntimeException
     *
     * @param  string $method
     * @param  string $nvp
     *
     * @return array | boolean
     */
    public function hash($method = null, $NVP = null)
    {
        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_URL, self::wppEndpoint);
        curl_setopt($curlHandler, CURLOPT_POST, true);
        curl_setopt($curlHandler, CURLOPT_VERBOSE, true);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);


        $requiredNVP  = 'METHOD=' . $method;
        $requiredNVP .= '&VERSION=' . self::VERSION;
        $requiredNVP .= '&USER=' . self::$wppUsername;
        $requiredNVP .= '&PWD=' . self::$wppPassword;
        $requiredNVP .= '&SIGNATURE=' . self::$wppSignature;

        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $requiredNVP . $NVP);
        $httpResponder = curl_exec($curlHandler);

        if (!$httpResponder) {
            throw new \RuntimeException(
                $method . ' failed: ' . curl_error($curlHandler) . ' ('
                . curl_errno($curlHandler) . ')'
            );
        }

        $responder = explode('&', $httpResponder);
        $parsedResponse = [];

        foreach ($responder as $response) {
            $responseArray = explode('=', $response);
            if (count($responseArray) >= 1) {
                $parsedResponse[$responseArray[0]] = urldecode($responseArray[1]);
            }
        }

        if ((count($parsedResponse) < 1) || !array_key_exists('ACK', $parsedResponse)) {
            throw new \RuntimeException(
                'Invalid HTTP Response for POST request (' . $requiredNVP
                . $NVP . ') to ' . self::$wppEndpoint
            );
        }

        return $parsedResponse;
    }
}
