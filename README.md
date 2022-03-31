# Paypal Web Payments Pro Package
[![Build Status](https://travis-ci.com/cpierce/paypal-wpp.svg?branch=master)](https://app.travis-ci.com/github/cpierce/paypal-wpp)

This package was written to interact with any PHP application, but I'll demo
using CakePHP 3.x to show how to connect to it.

## Composer Settings
First we'll want to add the library in via the `composer.json` file.
`"cpierce/paypal-wpp": "3.*"` will need to be added into your require as follows:

File: `composer.json`

```
{
    "name": "cakephp/app",
    "description": "CakePHP skeleton app",
    "homepage": "http://cakephp.org",
    "type": "project",
    "license": "MIT",
    "require": {
        "php": ">=5.5.9",
        "cakephp/cakephp": "~3.3",
        "mobiledetect/mobiledetectlib": "2.*",
        "cakephp/migrations": "~1.0",
        "cakephp/plugin-installer": "*",
        "cpierce/paypal-wpp": "3.*"
    },
    "require-dev": {
        "psy/psysh": "@stable",
        "cakephp/debug_kit": "~3.2",
        "cakephp/bake": "~1.1"
    },
    "suggest": {
        "phpunit/phpunit": "Allows automated tests to be run without system-wide install.",
        "cakephp/cakephp-codesniffer": "Allows to check the code against the coding standards used in CakePHP."
    },
    "autoload": {
        "psr-4": {
            "App\\": "src"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "App\\Test\\": "tests",
            "Cake\\Test\\": "./vendor/cakephp/cakephp/tests"
        }
    },
    "scripts": {
        "post-install-cmd": "App\\Console\\Installer::postInstall",
        "post-autoload-dump": "Cake\\Composer\\Installer\\PluginInstaller::postAutoloadDump"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

Once you have this change saved run `composer update` and let the package download
into the vendor app.

## General Configuration
To begin you'll need to configure your service username, password and signature.
The configuration will need to look something like this:

File: `config/bootstrap.php`

```
/**
 * Paypal WPP Payload
 */
Configure::write('PaypalWPP.username', 'username_api1.domain.com');
Configure::write('PaypalWPP.password', '5SWM6YY8YSUY888');
Configure::write('PaypalWPP.signature', 'tlArzO7mr5uXMO6.H2zPIuzAFYn4irhcVyzOPeiUcocJF.H3mGr');
```

## Making a transaction to PayPal WPP
After the configuration is setup you'll want to connect to PayPal WPP using the
library.

File: `src/Form/SalesForm.php`

```
<?php

namespace App\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use PaypalWPP\PaypalWPP;
use Cake\Core\Configure;

/**
 * Sales Form class.
 *
 * @extends Cake\Form\Form
 */
class SalesForm extends Form
{
    /**
     * Parse Data Array.
     *
     * @var array
     */
    protected $parseData = [];

    /**
     * Build Schema Method.
     *
     * @param Schema $schema
     *
     * @return Schema $schema
     */
    protected function _buildSchema(Schema $schema)
    {
        return $schema->addField('first_name', 'string')
            ->addField('last_email', 'string')
            ->addField('card_number', 'string')
            ->addField('amount', 'string');
    }

    /**
     * Build Validator Method.
     *
     * @param Validator $validator
     *
     * @return Validator $validator
     */
    protected function _buildValidator(Validator $validator)
    {
        return $validator
            ->notBlank('first_name', __('Your first name is required.'))
            ->notBlank('last_name', __('Your last name is required.'))
            ->creditCard('card_number', [
                'amex',
                'visa',
                'disc',
                'mc',
            ], __('Please enter a valid credit card number.'))
            ->notBlank('amount', __('Please enter an amount.'));
    }

    /**
     * Execute Method.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function _execute(array $data)
    {
        $paypal = new PaypalWPP(Configure::read('PaypalWPP'));

        $payment = $paypal->doDirectPayment($data);

        if ($payment['ACK'] == 'Success') {
            $this->parseData = [
                'transaction_id' => $payment['TRANSACTIONID'],
            ];
            return true;
        } else {
            $this->parseData = [
                'failure_message' => $payment['L_LONGMESSAGE0'],
                'failure_short'   => $payment['L_SHORTMESSAGE0'],
            ];
        }

        return false;
    }

    /**
     * Get Parse Data Method.
     *
     * @return string
     */
    public function getParseData()
    {
        return $this->parseData;
    }
}
```

File: `src/Controller/SalesController.php`

```
<?php

namespace App\Controller;

use App\Form\SalesForm;

/**
 * Sales Controller.
 */
class SalesController extends AppController
{
    /**
     * Add Method.
     */
    public function add()
    {
        $sales = new SalesForm();

        if ($this->request->is(['post', 'put'])) {
            $transaction_execute = $sales->execute($this->request->data);
            $transaction = $sales->getParseData();
            if ($transaction_execute) {
                $this->Flash->success(
                    __('Payment Completed Successfully: '.$transaction['transaction_id'])
                );
                $this->render('success');
            } else {
                $this->Flash->error(
                    __('Payment Failed: '.$transaction['failure_message'].'['.$transaction['failure_short'].']')
                );
            }
        }

        $this->set(compact('sales'));
    }
}
```
