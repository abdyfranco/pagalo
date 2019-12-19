<?php
/**
 * Clients module.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Module.Clients
 * @copyright  Copyright (c) 2018-2019 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Module;

class Clients extends \Pagalo\Pagalo
{
    public function getAll()
    {
        // Get company details
        $company = $this->getCompany();

        // Get company clients
        $result = $this->sendRequest('api/mi/clientes/' . $company->id);
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function get($client_id)
    {
        // Get all company clients
        $clients = $this->getAll();

        // Search the required client
        foreach ($clients as $client) {
            if ($client->id == $client_id) {
                return $client;
            }
        }

        return null;
    }

    public function create(array $client)
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

        $client = $this->search($params['email']);
        $this->sendRequest('api/mi/clientes/editar/' . $client[0]->id, $params, 'PUT', [
            'Content-Type: application/json;charset=UTF-8'
        ]);

        return !empty($client);
    }

    public function search($client_name)
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
}
