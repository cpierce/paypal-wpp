<?php
/**
 * Library for handling Paypal WPP Calls.
 *
 * @copyright Copyright (c) 2016, Chris Pierce
 * @author Chris Pierce <cpierce@csdurant.com>
 *
 * @link http://www.github.com/cpierce/paypal-wpp
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
    private $wppEndpoint = 'https://api-3t.paypal.com/nvp';

    /**
     * WPP username for connection.
     *
     * @var string
     */
    private $wppUsername;

    /**
     * WPP password for connection.
     *
     * @var string
     */
    private $wppPassword;

    /**
     * WPP signature for connection.
     *
     * @var string
     */
    private $wppSignature;

    /**
     * Construct Method.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->_setConfig($config);
    }

    /**
     * Set Config Method.
     *
     * @param array $config
     *
     */
    private function _setConfig($config = [])
    {
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
            $this->wppEndpoint = $config['endpoint'];
        }
    }

    /**
     * Get Config Method.
     *
     * @return array
     */
    private function _getConfig()
    {
        $config = [
            'version' => self::VERSION,
            'endpoint' => $this->wppEndpoint,
            'username' => $this->wppUsername,
            'password' => $this->wppPassword,
            'signature' => $this->wppSignature,
        ];

        return $config;
    }

    /**
     * Get Running Config Method.
     *
     * @return array
     */
    public function getRunningConfig()
    {
        return $this->_getConfig();
    }

    /**
     * Do Direct Payment Method.
     *
     * @param array $data
     *
     * @return array | bool
     */
    public function doDirectPayment($data)
    {
        $method = 'DoDirectPayment';

        $data['first_name'] = urlencode($data['first_name']);
        $data['last_name'] = urlencode($data['last_name']);
        $data['amount'] = str_replace(['$', ' ', ','], '', $data['amount']);
        $data['card_number'] = str_replace(['-', ' '], '', $data['card_number']);
        $data['expiration_date']['month'] = str_pad($data['expiration_date']['month'], 2, '0', STR_PAD_LEFT);

        switch ($data['card_number'][0]) {
            case 3:
                $data['card_type'] = 'Amex';
                break;
            case 5:
                $data['card_type'] = 'MasterCard';
                break;
            case 6:
                $data['card_type'] = 'Discover';
                break;
            default:
                $data['card_type'] = 'Visa';
                break;
        }

        $NVP  = '&PAYMENTACTION=Sale';
        $NVP .= '&AMT='.$data['amount'];
        $NVP .= '&CREDITCARDTYPE='.$data['card_type'];
        $NVP .= '&ACCT='.$data['card_number'];

        if (!empty($data['cvv2'])) {
            $NVP .= '&CVV2='.$data['cvv2'];
        }

        if (!empty($data['invoice_number'])) {
            $NVP .= '&INVNUM='.$data['invoice_number'];
        }

        $NVP .= '&EXPDATE='.$data['expiration_date']['month'].$data['expiration_date']['year'];
        $NVP .= '&FIRSTNAME='.$data['first_name'];
        $NVP .= '&LASTNAME='.$data['last_name'];
        $NVP .= '&COUNTRYCODE=US';
        $NVP .= '&CURRENCYCODE=USD';

        $response = $this->hash($method, $NVP);

        if (!empty($response)) {
            return $this->getParsedResponse($response);
        }

        return false;
    }

    /**
     * Web Payments Pro Hash Method.
     *
     * @throws \RuntimeException
     *
     * @param string $method
     * @param string $nvp
     *
     * @codeCoverageIgnore
     *
     * @return array | bool
     */
    public function hash($method = null, $NVP = null)
    {
        $curlHandler = curl_init();
        $config      = $this->_getConfig();

        curl_setopt($curlHandler, CURLOPT_URL, $config['endpoint']);
        curl_setopt($curlHandler, CURLOPT_POST, true);
        curl_setopt($curlHandler, CURLOPT_VERBOSE, true);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, true);

        $requiredNVP  = 'METHOD='.$method;
        $requiredNVP .= '&VERSION='.$config['version'];
        $requiredNVP .= '&USER='.$config['username'];
        $requiredNVP .= '&PWD='.$config['password'];
        $requiredNVP .= '&SIGNATURE='.$config['signature'];

        curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $requiredNVP.$NVP);
        $httpResponder = curl_exec($curlHandler);

        if (!$httpResponder) {
            throw new \RuntimeException(
                $method.' failed: '.curl_error($curlHandler).' ('
                .curl_errno($curlHandler).')'
            );
        }

        return $httpResponder;
    }

    /**
     * Get Parsed Response Method.
     *
     * @param  string $data
     *
     * @return array
     */
    public function getParsedResponse($data)
    {
        $responder      = explode('&', $data);
        $parsedResponse = [];

        foreach ($responder as $response) {
            $responseArray = explode('=', $response);
            if (count($responseArray) >= 1) {
                $parsedResponse[$responseArray[0]] = urldecode($responseArray[1]);
            }
        }

        if ((count($parsedResponse) < 1) || !array_key_exists('ACK', $parsedResponse)) {
            throw new \RuntimeException(
                'Invalid HTTP Response for POST request.'
            );
        }

        return $parsedResponse;
    }
}
