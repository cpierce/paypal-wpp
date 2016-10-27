<?php

namespace PaypalWPP\Test;

use PHPUnit\Framework\TestCase;
use PaypalWPP\PaypalWPP;

/**
 * Paypal WPP Test Class.
 */
class PaypalWPPTest extends TestCase
{

    /**
     * Provider Test Config Method.
     */
    public function providerTestConfig()
    {
        return [
            [
                [
                    'username'  => 'testing@testing.com',
                    'password'  => 'password!',
                    'signature' => 'ABCD1234',
                    'endpoint'  => 'https://api-3t.paypal.com/nvp',
                ],
            ],
        ];
    }

    /**
     * Test Get Running Config Method.
     *
     * @param array $config
     *
     * @dataProvider providerTestConfig
     */
    public function testGetRunningConfig($config)
    {
        $expected['username']  = urlencode($config['username']);
        $expected['password']  = urlencode($config['password']);
        $expected['signature'] = urlencode($config['signature']);
        $expected['endpoint']  = 'https://api-3t.paypal.com/nvp';
        $expected['version']   = '53.0';

        $paypal = new PaypalWPP($config);
        $result = $paypal->getRunningConfig();


        $this->assertEquals($expected, $result);
    }

    /**
     * Test Get Parsed Response Method.
     *
     * @param array $config
     *
     * @dataProvider providerTestConfig
     */
    public function testGetParsedResponse($config)
    {
        $data     = 'TRANSACTIONID=1234ABCD&ACK=Success';
        $expected = [
            'ACK'           => 'Success',
            'TRANSACTIONID' => '1234ABCD',
        ];

        $paypal = new PaypalWPP($config);
        $result = $paypal->getParsedResponse($data);

        $this->assertEquals($expected, $result);
    }

    /**
     * Test Get Parsed Response Exception Method.
     *
     * @param array $config
     *
     * @dataProvider providerTestConfig
     * @expectedException \RuntimeException
     */
    public function testGetParsedResponseException($config)
    {
        $data     = 'TRANSACTIONID=1234ABCD&INVOICE=2222';

        $paypal = new PaypalWPP($config);
        $result = $paypal->getParsedResponse($data);
    }

    /**
     * Test Do Direct Payment Method.
     *
     * @param array $config
     *
     * @dataProvider providerTestConfig
     */
    public function testDoDirectPayment($config)
    {
        $data     = [
            'first_name'      => 'Chris',
            'last_name'       => 'Pierce',
            'amount'          => '$48.97',
            'card_number'     => '4111-1111-1111-1111',
            'cvv2'            => '389',
            'invoice_number'  => '31337',
            'expiration_date' => [
                'month'       => 2,
                'year'        => 2099,
            ]
        ];

        $response = 'TRANSACTIONID=1234ABCD&ACK=Success';
        $expected = [
            'ACK'           => 'Success',
            'TRANSACTIONID' => '1234ABCD',
        ];

        $paypal = $this->getMockBuilder('PaypalWPP\PaypalWPP')
            ->setConstructorArgs([$config])
            ->setMethods(['hash'])
            ->getMock();

        $paypal->expects($this->once())
            ->method('hash')
            ->will($this->returnValue($response));

        $result = $paypal->doDirectPayment($data);

        $this->assertEquals($result, $expected);
    }
}
