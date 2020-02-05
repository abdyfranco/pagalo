<?php
/**
 * Clients module.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Module.Clients
 * @copyright  Copyright (c) 2018-2020 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Module;

use stdClass;

class Clients extends \Pagalo\Pagalo
{
    /**
     * Get all clients.
     *
     * @return null|array An array containing all the system clients.
     */
    public function getAll() : ?array
    {
        // Get company details
        $company = $this->getCompany();

        // Get company clients
        $result = $this->sendRequest('api/mi/clientes/' . $company->id);

        return (array) isset($result->datos) ? $result->datos : null;
    }

    /**
     * Get client.
     *
     * @param int $client_id The client's id.
     *
     * @return null|\stdClass An object containing the client's information.
     */
    public function get(int $client_id) : ?stdClass
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

    /**
     * Create a new client.
     *
     * @param array $client An array containing the following parameters:
     *   - @option string "nombre"     The client's first name.
     *   - @option string "apellido"   The client's last name.
     *   - @option string "email"      The client's email address.
     *   - @option string "telefono"   The client's phone number.
     *   - @option string "direccion"  The client's address.
     *   - @option string "pais"       The ISO 3166-1 Alpha 2 code of client's country.
     *   - @option string "state"      The ISO 3166-2 Alpha 2 code of client's state (only US and Canada), or the full
     *     name for the rest of the world.
     *   - @option string "postalcode" The client's postal code.
     *   - @option string "ciudad"     The client's city.
     *   - @option string "nit"        The client's NIT number (nnly for Guatemala).
     *
     * @return bool True if the client has been succesfully created.
     */
    public function create(array $client) : bool
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
        $this->sendRequest(
            'api/mi/clientes/crear/' . $company->id,
            $params,
            'POST',
            [
                'Content-Type: application/json;charset=UTF-8'
            ],
            true
        );

        // Get recently created client
        unset($params['identidad_empresa']);
        unset($params['id_empresa']);

        $client = $this->search($params['email']);
        $this->sendRequest(
            'api/mi/clientes/editar/' . $client[0]->id,
            $params,
            'PUT',
            [
                'Content-Type: application/json;charset=UTF-8'
            ],
            true
        );

        return !empty($client);
    }

    /**
     * Search a client.
     *
     * @param string $client_name The client's name to search.
     *
     * @return null|array An array containing all the matches for the search.
     */
    public function search(string $client_name) : ?array
    {
        $params = [
            'busqueda' => trim($client_name)
        ];
        $result = $this->sendRequest(
            'api/miV2/searchClient',
            $params,
            'POST',
            [
                'Content-Type: application/json;charset=UTF-8'
            ]
        );

        return (array) isset($result->datos) ? $result->datos : null;
    }
}
