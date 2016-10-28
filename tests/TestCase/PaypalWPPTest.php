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
                [ // config
                    'username'  => 'testing@testing.com',
                    'password'  => 'password!',
                    'signature' => 'ABCD1234',
                    'endpoint'  => 'https://api-3t.paypal.com/nvp',
                ],
                [ // data
                    'first_name'      => 'Chris',
                    'last_name'       => 'Pierce',
                    'amount'          => '$48.97',
                    'card_number'     => '4111-1111-1111-1111',
                    'cvv2'            => '389',
                    'invoice_number'  => '31337',
                    'expiration_date' => [
                        'month'       => 2,
                        'year'        => 2099,
                    ],
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
     * Test String Sent Exception Method.
     *
     * @expectedException \RuntimeException
     */
    public function testStringSentConfig()
    {
        $paypal = new PaypalWPP('hello');
    }

    /**
     * Test No Username Sent Exception Method
     *
     * @expectedException \RuntimeException
     */
    public function testNoUsernameSentConfig()
    {
        $paypal = new PaypalWPP([
            'username'  => 'testing@testing.com',
            'signature' => 'ABCD1234!'
        ]);
    }

    /**
     * Test No Password Sent Exception Method
     *
     * @expectedException \RuntimeException
     */
    public function testNoPasswordSentConfig()
    {
        $paypal = new PaypalWPP([
            'password'  => 'Password!',
            'signature' => 'ABCD1234!'
        ]);
    }

    /**
     * Test No Signature Sent Exception Method
     *
     * @expectedException \RuntimeException
     */
    public function testNoSignatureSentConfig()
    {
        $paypal = new PaypalWPP([
            'username'  => 'testing@testing.com',
            'password'  => 'Password!',
        ]);
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
    public function testDoDirectPayment($config, $data)
    {
        $response = 'TRANSACTIONID=1234ABCD&ACK=Success';
        $expected = [
            'ACK'           => 'Success',
            'TRANSACTIONID' => '1234ABCD',
        ];

        $paypal = $this->getMockBuilder('PaypalWPP\PaypalWPP')
            ->setConstructorArgs([$config])
            ->setMethods(['hash'])
            ->getMock();

        $clone = clone $paypal;
        $clone->expects($this->once())
            ->method('hash')
            ->will($this->returnValue(false));

        $paypal->expects($this->any())
            ->method('hash')
            ->will($this->returnValue($response));

        $result  = $paypal->doDirectPayment($data);

        $data['card_number'] = '5111-1111-1111-1111';
        $result2 = $paypal->doDirectPayment($data);

        $data['card_number'] = '6111-1111-1111-1111';
        $result3 = $paypal->doDirectPayment($data);

        $data['card_number'] = '3111-111111-11111';
        $result4 = $paypal->doDirectPayment($data);

        $result5 = $clone->doDirectPayment($data);

        $this->assertEquals($result, $expected);
        $this->assertEquals($result2, $expected);
        $this->assertEquals($result3, $expected);
        $this->assertEquals($result4, $expected);
        $this->assertFalse($result5);
    }
}
