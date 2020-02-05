<?php
/**
 * Card object.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Object.Card
 * @copyright  Copyright (c) 2018-2020 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Object;

class Card
{
    /**
     * @var string The credit card number.
     */
    private $card_number;

    /**
     * @var string The cardholder name.
     */
    private $card_name;

    /**
     * @var array The expiration date.
     */
    private $expiration_date;

    /**
     * @var string The card verification value number.
     */
    private $cvv_number;

    /**
     * Card constructor.
     *
     * @param string $card_number     The card number.
     * @param string $card_name       The cardholder name.
     * @param string $expiration_date The expiration date.
     * @param string $cvv_number      The CVV number.
     *
     * @throws \Pagalo\Error\InvalidCard
     */
    public function __construct(string $card_number, string $card_name, string $expiration_date, string $cvv_number)
    {
        $this->card_number = $card_number;
        $this->card_name   = $card_name;
        $this->cvv_number  = $cvv_number;

        $this->expiration_date = explode('/', $expiration_date, 2);

        if (!$this->luhn($card_number)) {
            throw new \Pagalo\Error\InvalidCard('The provided card number cannot be verified with the Luhn algorithm');
        }
    }

    /**
     * Get the card data array.
     *
     * @return array An array containing all the card information.
     */
    public function getCardArray(): array
    {
        return [
            'accountNumber'   => $this->card_number,
            'nameCard'        => $this->card_name,
            'expirationMonth' => $this->expiration_date[0],
            'expirationYear'  => $this->expiration_date[1],
            'cvNumber'        => $this->cvv_number
        ];
    }

    /**
     * Get the card number.
     *
     * @return string The card number.
     */
    public function getCardNumber(): string
    {
        return $this->card_number;
    }

    /**
     * Get the cardholder name.
     *
     * @return string
     */
    public function getCardName(): string
    {
        return $this->card_name;
    }

    /**
     * Get an array with the expiration date.
     *
     * @return array The expiration date.
     */
    public function getExpirationDate(): array
    {
        return $this->expiration_date;
    }

    /**
     * Get the CVV number.
     *
     * @return string The CVV number.
     */
    public function getCvv(): string
    {
        return $this->cvv_number;
    }

    /**
     * Validates if the given card number it's valid and can be validated using the Luhn algorithm.
     *
     * @param $number The card number.
     *
     * @return bool True if the card it's valid, false otherwise.
     */
    public function luhn($number): bool
    {
        // Force the value to be a string as this method uses string functions
        $number = (string) $number;

        if (!ctype_digit($number)) {
            return false;
        }

        // Check number length
        $length = strlen($number);

        // Calculate the checksum of the card number
        $checksum = 0;

        for ($i = $length - 1; $i >= 0; $i -= 2) {
            $checksum += substr($number, $i, 1);
        }

        for ($i = $length - 2; $i >= 0; $i -= 2) {
            $double = substr($number, $i, 1) * 2;

            $checksum += ($double >= 10) ? ($double - 9) : $double;
        }

        // If the checksum is a multiple of 10, the number is valid
        return $checksum % 10 === 0;
    }
}
