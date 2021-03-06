# Pagalo Merchant PHP SDK
Provides an easy-to-use class for generating payment requests using Pagalo.

```php
<?php

use Pagalo\Module\Payments;

$Payments = new Payments('username', 'password');

$client_id = '1000';
$description = 'Invoice #1234';
$amount = 100;
$currency = 'USD';

$request = $Payments->request($client_id, $description, $amount, $currency);
```

## Requirements
PHP 7.1+. Other than that, this library has no external requirements.

## Installation
You can install this library via Composer.
```bash
$ composer require abdyfranco/pagalo
```

## License
The MIT License (MIT). Please see "LICENSE.md" File for more information.