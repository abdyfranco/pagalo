<?php
declare(strict_types = 1);

use Tester\Assert;
use Pagalo\Object\Card;

require __DIR__ . '/../../bootstrap.php';

/**
 * Mock the original class.
 */
$Card = new Card('4242424242424242', 'John Smith', '12/2025', '045');

/**
 * Card::getCardArray() Test
 *
 * @assert type Check if the type of the response it's an array.
 * @assert contains Check if the response contains the card number.
 */
Assert::type('array', $Card->getCardArray());
Assert::contains('4242424242424242', $Card->getCardArray());

/**
 * Card::getCardNumber() Test
 *
 * @assert equal Check if the response it's equal to the card number.
 */
Assert::equal('4242424242424242', $Card->getCardNumber());

/**
 * Card::getCardName() Test
 *
 * @assert equal Check if the response it's equal to the cardholder name.
 */
Assert::equal('John Smith', $Card->getCardName());

/**
 * Card::getExpirationDate() Test
 *
 * @assert type Check if the type of the response it's an array.
 * @assert contains Check if the response contains the year of the expiration date.
 */
Assert::type('array', $Card->getExpirationDate());
Assert::contains('2025', $Card->getExpirationDate());

/**
 * Card::getCvv() Test
 *
 * @assert equal Check if the function returns the original CVV code.
 */
Assert::equal('045', $Card->getCvv());

/**
 * Card::luhn() Test
 *
 * @assert true Check if the function it's able to validate a valid card number.
 * @assert false Check if the function it's able to detect a not valid card number.
 */
Assert::true($Card->luhn('4242424242424242'));
Assert::false($Card->luhn('1234123412341234'));

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
