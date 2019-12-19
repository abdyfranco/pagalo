<?php
/**
 * Card object.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Object.Card
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Object;

class Card
{
    private $card_number;

    private $card_name;

    private $expiration_date;

    private $cvv_number;

    public function __construct($card_number, $card_name, $expiration_date, $cvv_number)
    {
        $this->card_number = $card_number;
        $this->card_name   = $card_name;
        $this->cvv_number  = $cvv_number;

        $this->expiration_date = explode('/', $expiration_date, 2);

        if (!$this->luhn($card_number)) {
            throw new \Pagalo\Error\InvalidCard('The provided card number cannot be verified with the Luhn algorithm');
        }
    }

    public function getCardArray()
    {
        return [
            'accountNumber'   => $this->card_number,
            'nameCard'        => $this->card_name,
            'expirationMonth' => $this->expiration_date[0],
            'expirationYear'  => $this->expiration_date[1],
            'cvNumber'        => $this->cvv_number
        ];
    }

    public function getCardNumber()
    {
        return $this->card_number;
    }

    public function getCardName()
    {
        return $this->card_name;
    }

    public function getExpirationDate()
    {
        return $this->expiration_date;
    }

    public function getCvv()
    {
        return $this->cvv_number;
    }

    protected function luhn($number)
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
