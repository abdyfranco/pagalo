# Pagalo Non-Merchant PHP SDK
Provides an easy-to-use class for generating payment requests using Pagalo.

```php
<?php

use Pagalo\NonMerchant;

$NonMerchant = new NonMerchant('username', 'password');

$client_id = '1000';
$description = 'Invoice #1234';
$amount = 100;
$currency = 'USD';

$request = $NonMerchant->requestPayment($client_id, $description, $amount, $currency);
```

## Requirements
PHP 5.6+. Other than that, this library has no external requirements.

## Installation
You can install this library via Composer.
```bash
$ composer require abdyfranco/pagalo
```

## License
The MIT License (MIT). Please see "LICENSE.md" File for more information.