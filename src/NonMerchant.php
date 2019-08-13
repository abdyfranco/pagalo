<?php
/**
 * Provides an easy-to-use class for generating payment requests using
 * Pagalo.
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
    private $endpoint = 'https://app.pagalocard.com/';

    private $username;

    private $password;

    private $session_dir;

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
        $params  = [
            'identidad_empresa' => $company->identidad_empresa,
            'id_empresa'        => $company->id,
            'nombre'            => null,
            'apellido'          => null,
            'email'             => null,
            'telefono'          => null,
            'direccion'         => null,
            'pais'              => null,
            'state'             => null,
            'postalcode'        => '00000',
            'ciudad'            => null,
            'nit'               => 'C/F',
            'adicional'         => [
                'titulos'     => [],
                'descripcion' => []
            ]
        ];
        $params  = array_merge($params, $client);
        $headers = [
            'Content-Type: application/json;charset=UTF-8'
        ];
        $result  = $this->sendRequest('api/mi/clientes/crear/' . $company->id, $params, 'POST', $headers);

        return !empty($result);
    }

    public function searchClient($client_name)
    {
        $params  = [
            'busqueda' => trim($client_name)
        ];
        $headers = [
            'Content-Type: application/json;charset=UTF-8'
        ];
        $result  = $this->sendRequest('api/miV2/searchClient', $params, 'POST', $headers);
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
        $headers = [
            'Content-Type: application/json;charset=UTF-8'
        ];
        $result  = $this->sendRequest('api/miV2/searchClient', $params, 'POST', $headers);
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
        $headers     = [
            'Content-Type: application/json;charset=UTF-8'
        ];
        $transaction = $this->sendRequest('api/miV2/asignarClient', $params, 'POST', $headers);
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
        $payment = $this->sendRequest('api/miV2/solicitud/enviarsolicitudl', $params, 'POST', $headers);
        $payment = json_decode($payment);

        // Build response
        $response = null;

        if (isset($payment->url)) {
            $response = (object) array_merge((array) $transaction, (array) $payment);
        }

        return $response;
    }
}
