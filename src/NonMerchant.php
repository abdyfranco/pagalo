<?php
/**
 * Provides an easy-to-use class for generating non-merchant payment requests
 * using Pagalo.
 *
 * @package    Pagalo
 * @subpackage Pagalo.NonMerchant
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo;

class NonMerchant
{
    protected $endpoint = 'https://app.pagalocard.com/';

    protected $username;

    protected $password;

    protected $session_dir;

    public function __construct($username, $password, $session_dir = null)
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

    protected function sendRequest($function, array $params = [], $method = 'GET', array $headers = [])
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

        // Close request
        curl_close($curl);

        return $result;
    }

    private function authenticate()
    {
        $token  = $this->getToken();
        $params = [
            '_token'   => $token,
            'email'    => $this->username,
            'password' => $this->password
        ];
        $result = $this->sendRequest('login', $params, 'POST');

        return strpos($result, 'http-equiv') !== false;
    }

    protected function getToken()
    {
        $result = $this->sendRequest('login');

        if (strpos($result, '_token') !== false) {
            $html = explode('name="_token" value="', $result, 2);
        } elseif (strpos($result, 'csrf-token') !== false) {
            $html = explode('name="csrf-token" content="', $result, 2);
        } else {
            // Logout from the application
            $this->sendRequest('logout');

            return $this->getToken();
        }

        if (isset($html[1])) {
            $html = explode('">', $html[1], 2);

            return $html[0];
        } else {
            throw new Error\Authentication('An error occurred trying to get the authorization token');
        }

        return null;
    }

    public function formatField($value)
    {
        $accents    = [
            'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô',
            'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë',
            'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă',
            'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę',
            'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ',
            'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń',
            'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś',
            'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů',
            'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư',
            'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ',
            'ǿ'
        ];
        $characters = [
            'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O',
            'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a',
            'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E',
            'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i',
            'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N',
            'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S',
            's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u',
            'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u',
            'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O',
            'o'
        ];

        return trim(str_replace($accents, $characters, $value));
    }

    public function getUser()
    {
        $result = $this->sendRequest('api/miV2/myUser');
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function getCompany()
    {
        $user = $this->getUser();

        return $user->empresa;
    }

    public function getPlan()
    {
        $result = $this->sendRequest('api/mi/configuracionPlan');
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function getAllClients()
    {
        // Get company details
        $company = $this->getCompany();

        // Get company clients
        $result = $this->sendRequest('api/mi/clientes/' . $company->id);
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function getClient($client_id)
    {
        // Get all company clients
        $clients = $this->getAllClients();

        // Search the required client
        foreach ($clients as $client) {
            if ($client->id == $client_id) {
                return $client;
            }
        }

        return null;
    }

    public function createClient(array $client)
    {
        // Get company details
        $company = $this->getCompany();

        // Build client parameters
        $params = [
            'identidad_empresa' => $company->identidad_empresa,
            'id_empresa'        => $company->id,
            'nombre'            => '',
            'apellido'          => '',
            'email'             => '',
            'telefono'          => '',
            'direccion'         => '',
            'pais'              => 'GT',
            'state'             => 'GT',
            'postalcode'        => '01001',
            'ciudad'            => 'Guatemala',
            'nit'               => 'C/F',
            'adicional'         => [
                'titulos'     => [],
                'descripcion' => []
            ]
        ];
        $params = array_merge($params, $client);

        // Remove state parameter for all countries, except US and Canada
        if ($params['pais'] !== 'US' && $params['pais'] !== 'CA') {
            $params['state'] = '';
        }

        // Remove postal code parameter for Guatemala
        if ($params['pais'] == 'GT') {
            $params['postalcode'] = '';
        }

        // Force two-letter states, if the provided country is the US or Canada
        if (($params['pais'] == 'US' || $params['pais'] == 'CA') && strlen($params['state']) > 2) {
            $params['state'] = strtoupper(substr($params['state'], 0, 2));
        }

        // Format client name and address
        $params['nombre']    = $this->formatField($params['nombre']);
        $params['apellido']  = $this->formatField($params['apellido']);
        $params['direccion'] = $this->formatField($params['direccion']);
        $params['ciudad']    = $this->formatField($params['ciudad']);

        // Send request
        $this->sendRequest('api/mi/clientes/crear/' . $company->id, $params, 'POST', [
            'Content-Type: application/json;charset=UTF-8'
        ]);

        // Get recently created client
        unset($params['identidad_empresa']);
        unset($params['id_empresa']);

        $client = $this->searchClient($params['email']);
        $this->sendRequest('api/mi/clientes/editar/' . $client[0]->id, $params, 'PUT', [
            'Content-Type: application/json;charset=UTF-8'
        ]);

        return !empty($client);
    }

    public function searchClient($client_name)
    {
        $params  = [
            'busqueda' => trim($client_name)
        ];
        $result  = $this->sendRequest('api/miV2/searchClient', $params, 'POST', [
            'Content-Type: application/json;charset=UTF-8'
        ]);
        $result  = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function getAllProducts()
    {
        // Get company details
        $company = $this->getCompany();

        // Get company clients
        $result = $this->sendRequest('api/mi/solicitud/solicitudes/' . $company->id);
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function searchProduct($product_name)
    {
        $params  = [
            'dato'     => trim($product_name),
            'busqueda' => trim($product_name),
        ];
        $result  = $this->sendRequest('api/miV2/searchClient', $params, 'POST', [
            'Content-Type: application/json;charset=UTF-8'
        ]);
        $result  = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function getAllPayments()
    {
        // Get company details
        $company = $this->getCompany();

        // Get company clients
        $result = $this->sendRequest('api/mi/solicitud/solicitudes/' . $company->id);
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function getPayment($transaction_id)
    {
        // Get all company payments
        $payments = $this->getAllPayments();

        // Search the required payment
        foreach ($payments as $payment) {
            if ($payment->id_transaccion == $transaction_id) {
                return $payment;
            }
        }

        return null;
    }

    public function requestPayment($client_id, $description, $amount, $currency = 'USD')
    {
        // Get client
        $client              = $this->getClient($client_id);
        $client->id_cliente  = $client_id;
        $client->tipoTransac = 'S';

        // Remove unnecessary client properties
        unset($client->empresa);
        unset($client->adicional);

        // Assign the client to the transaction
        $params      = (array) $client;
        $transaction = $this->sendRequest('api/miV2/asignarClient', $params, 'POST', [
            'Content-Type: application/json;charset=UTF-8'
        ]);
        $transaction = json_decode($transaction);

        // Build the payment
        $params  = [
            'id_transaccion' => $transaction->id_transaccion,
            'id_empresa'     => $transaction->cliente->id_empresa,
            'carrito'        => [
                [
                    'precio'      => number_format($amount, 2),
                    'sku'         => 'sku001',
                    'nombre'      => $description,
                    'id_producto' => 0,
                    'cantidad'    => 1,
                    'subtotal'    => number_format($amount, 2)
                ]
            ],
            'moneda'         => $currency,
            'tipoPago'       => 'CY'
        ];
        $payment = $this->sendRequest('api/miV2/solicitud/enviarsolicitudl', $params, 'POST', [
            'Content-Type: application/json;charset=UTF-8'
        ]);
        $payment = json_decode($payment);

        // Build response
        $response = null;

        if (isset($payment->url)) {
            $response = (object) array_merge((array) $transaction, (array) $payment);
        }

        return $response;
    }
}
