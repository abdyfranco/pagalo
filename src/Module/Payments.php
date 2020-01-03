<?php
/**
 * Payments module.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Module.Payments
 * @copyright  Copyright (c) 2018-2020 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Module;

class Payments extends \Pagalo\Pagalo
{
    private $merchant_id = 'visanetgt_jupiter';

    private $organization_id = 'k8vif92e';

    private function collectData($endpoint)
    {
        $curl = curl_init();

        // Set request headers
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        // Build URL
        $url = 'https://h.online-metrix.net/fp/' . $endpoint;

        // Make request
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.2 Safari/605.1.15');
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

        // Create and save the request cookie
        $cookie = $this->session_dir . md5($this->username) . '.txt';

        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);

        // Execute request
        $result = curl_exec($curl);

        // Close request
        curl_close($curl);

        return $result;
    }

    private function getDeviceFingerprint()
    {
        // Set session ID
        $session_id = round(microtime(true) * 1000);

        // Data collection endpoints
        $data_collect = [
            'clear.png?org_id=' . $this->organization_id . '&session_id=' . $this->merchant_id . $session_id . '&m=1',
            'clear.png?org_id=' . $this->organization_id . '&session_id=' . $this->merchant_id . $session_id . '&m=2',
            'fp.swf?org_id=' . $this->organization_id . '&session_id=' . $this->merchant_id . $session_id,
            'tags.js?org_id=' . $this->organization_id . '&session_id=' . $this->merchant_id . $session_id
        ];

        foreach ($data_collect as $data_endpoint) {
            $this->collectData($data_endpoint);
        }

        return $session_id;
    }

    public function getAll()
    {
        // Get company details
        $company = $this->getCompany();

        // Get company clients
        $result = $this->sendRequest('api/mi/solicitud/solicitudes/' . $company->id);
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function get($transaction_id)
    {
        // Get all company payments
        $payments = $this->getAll();

        // Search the required payment
        foreach ($payments as $payment) {
            if ($payment->id_transaccion == $transaction_id) {
                return $payment;
            }
        }

        return null;
    }

    public function request($client_id, $description, $amount, $currency = 'USD')
    {
        // Get client
        $Clients = new Clients($this->username, $this->password, $this->session_dir);
        $client  = $Clients->get($client_id);

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

    public function process($client_id, \Pagalo\Object\Card $card, $description, $amount, $currency = 'USD')
    {
        // Get client
        $Clients = new Clients($this->username, $this->password, $this->session_dir);
        $client  = $Clients->get($client_id);

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

        // Initialize the transaction
        $this->sendRequest('api/mi/totalVentasComercio');

        // Decode the credit card token
        $credit_card = $card->getCardArray();

        // Get device fingerprint
        $fingerprint = $this->getDeviceFingerprint();

        // Process the payment
        $params  = [
            'moneda'          => $currency,
            'clienteEmail'    => $transaction->cliente->email,
            'clienteNombre'   => $transaction->cliente->nombre,
            'clienteApellido' => $transaction->cliente->apellido,
            'deviceFinger'    => $fingerprint,
            'carrito'         => [
                [
                    'precio'      => number_format($amount, 2),
                    'sku'         => 'sku001',
                    'nombre'      => $description,
                    'id_producto' => 0,
                    'cantidad'    => 1,
                    'subtotal'    => number_format($amount, 2)
                ]
            ]
        ];
        $params  = array_merge($params, $credit_card);
        $payment = $this->sendRequest('api/miV2/enviarventa/' . $transaction->id_transaccion, $params, 'POST', $headers);
        $payment = json_decode($payment);

        // Build response
        $response = null;

        if (isset($payment->decision)) {
            $response = (object) array_merge((array) $transaction, (array) $payment);
        }

        return $response;
    }
}
