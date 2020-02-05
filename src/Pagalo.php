<?php
/**
 * Provides an easy-to-use class for generating merchant and non-merchant
 * payment requests using Pagalo.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Pagalo
 * @copyright  Copyright (c) 2018-2020 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo;

use stdClass;

class Pagalo
{
    /**
     * @var string The Pagalo app endpoint.
     */
    protected $endpoint = 'https://app.pagalocard.com/';

    /**
     * @var string The Pagalo username.
     */
    protected $username;

    /**
     * @var string The Pagalo password.
     */
    protected $password;

    /**
     * @var string The session directory.
     */
    protected $session_dir;

    /**
     * Pagalo constructor.
     *
     * @param string      $username    The Pagalo username.
     * @param string      $password    The Pagalo password.
     * @param string|null $session_dir The session directory.
     *
     * @throws \Pagalo\Error\Authentication
     */
    public function __construct(string $username, string $password, string $session_dir = null)
    {
        $this->username = $username;
        $this->password = $password;

        // Set the session directory
        if (is_null($session_dir)) {
            $this->session_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        } else {
            $this->session_dir = rtrim($session_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        // Authenticate in to the Pagalo dashboard
        $authentication = $this->authenticate();

        if (!$authentication) {
            throw new Error\Authentication('The given combination of username and password is incorrect');
        }
    }

    /**
     * Makes a request to the API.
     *
     * @param string $function The function of the API to be called.
     * @param array  $params   An array with the parameters that will be passed to the function called.
     * @param string $method   The HTTP method to be used for the request.
     * @param array  $headers  The headers to be sent in the HTTP request.
     * @param bool   $raw      True to return the RAW response, false to return the parsed response.
     *
     * @return mixed An object containing the response of the API request or the RAW response.
     */
    public function sendRequest(string $function, array $params = [], string $method = 'GET', array $headers = [], bool $raw = false)
    {
        $curl = curl_init();

        // Set request headers
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            if (in_array('Content-Type: application/json;charset=UTF-8', $headers)) {
                $params = json_encode($params);
            }
        }

        // Build GET request
        if ($method == 'GET') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

            if (!empty($params)) {
                $get = '?' . http_build_query($params);
            }
        }

        // Build POST request
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_POST, true);

            if (!empty($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            }
        }

        // Build PUT request
        if ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');

            if (!empty($params)) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            }
        }

        // Build URL
        $url = $this->endpoint . $function . (isset($get) ? $get : '');

        // Make request
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Create and save the request cookie
        $cookie = $this->session_dir . md5($this->username) . '.txt';

        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);

        // Get result
        $result = curl_exec($curl);

        if (!$raw) {
            $result = json_decode($result);
        }

        // Close request
        curl_close($curl);

        return $result;
    }

    /**
     * Authenticate API user.
     *
     * @return bool True if the user has successfully authenticated, otherwise false.
     * @throws \Pagalo\Error\Authentication
     */
    private function authenticate() : bool
    {
        $token  = $this->getToken();
        $params = [
            '_token'   => $token,
            'email'    => $this->username,
            'password' => $this->password
        ];
        $result = $this->sendRequest('login', $params, 'POST', [], true);

        return strpos($result, 'http-equiv') !== false;
    }

    /**
     * Get the authentication token.
     *
     * @return string The authentication token.
     * @throws \Pagalo\Error\Authentication
     */
    public function getToken() : string
    {
        $result = $this->sendRequest('login', [], 'GET', [], true);

        if (strpos($result, '_token') !== false) {
            $html = explode('name="_token" value="', $result, 2);
        } else if (strpos($result, 'csrf-token') !== false) {
            $html = explode('name="csrf-token" content="', $result, 2);
        } else {
            // Logout from the application
            $this->sendRequest('logout', [], 'GET', [], true);

            return $this->getToken();
        }


        if (isset($html[1])) {
            $html = explode('">', $html[1], 2);

            return $html[0];
        } else {
            throw new Error\Authentication('An error occurred trying to get the authorization token');
        }
    }

    /**
     * It formats a string, replacing all special characters.
     *
     * @param string $value The string to be formatted.
     *
     * @return string The formatted string.
     */
    public function formatField(string $value) : string
    {
        $accents    = [
            'À',
            'Á',
            'Â',
            'Ã',
            'Ä',
            'Å',
            'Æ',
            'Ç',
            'È',
            'É',
            'Ê',
            'Ë',
            'Ì',
            'Í',
            'Î',
            'Ï',
            'Ð',
            'Ñ',
            'Ò',
            'Ó',
            'Ô',
            'Õ',
            'Ö',
            'Ø',
            'Ù',
            'Ú',
            'Û',
            'Ü',
            'Ý',
            'ß',
            'à',
            'á',
            'â',
            'ã',
            'ä',
            'å',
            'æ',
            'ç',
            'è',
            'é',
            'ê',
            'ë',
            'ì',
            'í',
            'î',
            'ï',
            'ñ',
            'ò',
            'ó',
            'ô',
            'õ',
            'ö',
            'ø',
            'ù',
            'ú',
            'û',
            'ü',
            'ý',
            'ÿ',
            'Ā',
            'ā',
            'Ă',
            'ă',
            'Ą',
            'ą',
            'Ć',
            'ć',
            'Ĉ',
            'ĉ',
            'Ċ',
            'ċ',
            'Č',
            'č',
            'Ď',
            'ď',
            'Đ',
            'đ',
            'Ē',
            'ē',
            'Ĕ',
            'ĕ',
            'Ė',
            'ė',
            'Ę',
            'ę',
            'Ě',
            'ě',
            'Ĝ',
            'ĝ',
            'Ğ',
            'ğ',
            'Ġ',
            'ġ',
            'Ģ',
            'ģ',
            'Ĥ',
            'ĥ',
            'Ħ',
            'ħ',
            'Ĩ',
            'ĩ',
            'Ī',
            'ī',
            'Ĭ',
            'ĭ',
            'Į',
            'į',
            'İ',
            'ı',
            'Ĳ',
            'ĳ',
            'Ĵ',
            'ĵ',
            'Ķ',
            'ķ',
            'Ĺ',
            'ĺ',
            'Ļ',
            'ļ',
            'Ľ',
            'ľ',
            'Ŀ',
            'ŀ',
            'Ł',
            'ł',
            'Ń',
            'ń',
            'Ņ',
            'ņ',
            'Ň',
            'ň',
            'ŉ',
            'Ō',
            'ō',
            'Ŏ',
            'ŏ',
            'Ő',
            'ő',
            'Œ',
            'œ',
            'Ŕ',
            'ŕ',
            'Ŗ',
            'ŗ',
            'Ř',
            'ř',
            'Ś',
            'ś',
            'Ŝ',
            'ŝ',
            'Ş',
            'ş',
            'Š',
            'š',
            'Ţ',
            'ţ',
            'Ť',
            'ť',
            'Ŧ',
            'ŧ',
            'Ũ',
            'ũ',
            'Ū',
            'ū',
            'Ŭ',
            'ŭ',
            'Ů',
            'ů',
            'Ű',
            'ű',
            'Ų',
            'ų',
            'Ŵ',
            'ŵ',
            'Ŷ',
            'ŷ',
            'Ÿ',
            'Ź',
            'ź',
            'Ż',
            'ż',
            'Ž',
            'ž',
            'ſ',
            'ƒ',
            'Ơ',
            'ơ',
            'Ư',
            'ư',
            'Ǎ',
            'ǎ',
            'Ǐ',
            'ǐ',
            'Ǒ',
            'ǒ',
            'Ǔ',
            'ǔ',
            'Ǖ',
            'ǖ',
            'Ǘ',
            'ǘ',
            'Ǚ',
            'ǚ',
            'Ǜ',
            'ǜ',
            'Ǻ',
            'ǻ',
            'Ǽ',
            'ǽ',
            'Ǿ',
            'ǿ',
            '#'
        ];
        $characters = [
            'A',
            'A',
            'A',
            'A',
            'A',
            'A',
            'AE',
            'C',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'D',
            'N',
            'O',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'Y',
            's',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'ae',
            'c',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'n',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'A',
            'a',
            'A',
            'a',
            'A',
            'a',
            'C',
            'c',
            'C',
            'c',
            'C',
            'c',
            'C',
            'c',
            'D',
            'd',
            'D',
            'd',
            'E',
            'e',
            'E',
            'e',
            'E',
            'e',
            'E',
            'e',
            'E',
            'e',
            'G',
            'g',
            'G',
            'g',
            'G',
            'g',
            'G',
            'g',
            'H',
            'h',
            'H',
            'h',
            'I',
            'i',
            'I',
            'i',
            'I',
            'i',
            'I',
            'i',
            'I',
            'i',
            'IJ',
            'ij',
            'J',
            'j',
            'K',
            'k',
            'L',
            'l',
            'L',
            'l',
            'L',
            'l',
            'L',
            'l',
            'l',
            'l',
            'N',
            'n',
            'N',
            'n',
            'N',
            'n',
            'n',
            'O',
            'o',
            'O',
            'o',
            'O',
            'o',
            'OE',
            'oe',
            'R',
            'r',
            'R',
            'r',
            'R',
            'r',
            'S',
            's',
            'S',
            's',
            'S',
            's',
            'S',
            's',
            'T',
            't',
            'T',
            't',
            'T',
            't',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'W',
            'w',
            'Y',
            'y',
            'Y',
            'Z',
            'z',
            'Z',
            'z',
            'Z',
            'z',
            's',
            'f',
            'O',
            'o',
            'U',
            'u',
            'A',
            'a',
            'I',
            'i',
            'O',
            'o',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'U',
            'u',
            'A',
            'a',
            'AE',
            'ae',
            'O',
            'o',
            'No.'
        ];

        return trim(str_replace($accents, $characters, $value));
    }

    /**
     * It obtains the data of the user from whom the API is connected.
     *
     * @return null|\stdClass Returns an object which contains the data of the current user, such as the ID, Mail,
     *     Name, etc...
     */
    public function getUser() : ?stdClass
    {
        $result = $this->sendRequest('api/miV2/myUser');

        return isset($result->datos) ? $result->datos : null;
    }

    /**
     * Get the company data.
     *
     * @return null|\stdClass An object containing all the related data of the company, such as Bank Accounts, Legal
     *     Representative, etc...
     */
    public function getCompany() : ?stdClass
    {
        $user = $this->getUser();

        return isset($user->empresa) ? $user->empresa : null;
    }

    /**
     * Get the current subscription plan.
     *
     * @return null|\stdClass An object containing the current subscription plan.
     */
    public function getPlan() : ?stdClass
    {
        $result = $this->sendRequest('api/mi/configuracionPlan');

        return isset($result->datos) ? $result->datos : null;
    }
}
