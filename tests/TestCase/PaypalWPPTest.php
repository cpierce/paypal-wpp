<?php
declare(strict_types = 1);

namespace PaypalWPP\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PaypalWPP\PaypalWPP;

/**
 * Paypal WPP Test Class.
 */
class PaypalWPPTest extends TestCase
{

    /**
     * Provider Test Config Method.
     *
     * @return array<mixed>
     */
    public static function providerTestConfig(): array
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
     * @param array<mixed> $config
     * @return void
     */
    #[DataProvider('providerTestConfig')]
    public function testGetRunningConfig($config = []): void
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
     * @throws \RuntimeException
     * @return void
     */
    public function testStringSentConfig(): void
    {
        // Expect a RuntimeException when an invalid config (string) is sent
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config payload required');

        // Pass a string to the contructor, which should trigger an exception
        new PaypalWPP('hello');
    }

    /**
     * Test No Username Sent Exception Method
     *
     * @throws \RuntimeException
     * @return void
     */
    public function testNoUsernameSentConfig(): void
    {
        $this->expectException(\RuntimeException::class);
        $paypal = new PaypalWPP([
            'username'  => 'testing@testing.com',
            'signature' => 'ABCD1234!'
        ]);
    }

    /**
     * Test No Password Sent Exception Method
     *
     * @throws \RuntimeException
     */
    public function testNoPasswordSentConfig(): void
    {
        $this->expectException(\RuntimeException::class);
        $paypal = new PaypalWPP([
            'password'  => 'Password!',
            'signature' => 'ABCD1234!'
        ]);
    }

    /**
     * Test No Signature Sent Exception Method
     *
     * @throws \RuntimeException
     */
    public function testNoSignatureSentConfig(): void
    {
        $this->expectException(\RuntimeException::class);
        $paypal = new PaypalWPP([
            'username'  => 'testing@testing.com',
            'password'  => 'Password!',
        ]);
    }

    /**
     * Test Get Parsed Response Method.
     *
     * @param array<mixed> $config
     *
     * @throws \RuntimeException
     * @return void
     */
    #[DataProvider('providerTestConfig')]
    public function testGetParsedResponse($config = []): void
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
     * @param array<mixed> $config
     *
     * @throws \RuntimeException
     * @return void
     */
    #[DataProvider('providerTestConfig')]
    public function testGetParsedResponseException($config = []): void
    {
        $this->expectException(\RuntimeException::class);
        $data     = 'TRANSACTIONID=1234ABCD&INVOICE=2222';

        $paypal = new PaypalWPP($config);
        $result = $paypal->getParsedResponse($data);
    }

    /**
     * Test Do Direct Payment Method.
     *
     * @param array<mixed> $config
     * @param array<mixed> $data
     * @return void
     */
    #[DataProvider('providerTestConfig')]
    public function testDoDirectPayment($config =[], $data = []): void
    {
        $response = 'TRANSACTIONID=1234ABCD&ACK=Success';
        $expected = [
            'ACK'           => 'Success',
            'TRANSACTIONID' => '1234ABCD',
        ];

        $paypal = $this->getMockBuilder('PaypalWPP\PaypalWPP')
            ->setConstructorArgs([$config])
            ->onlyMethods(['hash'])
            ->getMock();

        $clone = clone $paypal;
        $clone->expects($this->once())
            ->method('hash')
            ->willReturn(false);

        $paypal->expects($this->any())
            ->method('hash')
            ->willReturn($response);

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
