<?php
declare(strict_types = 1);

use Tester\Assert;
use Pagalo\Module\Clients;

require __DIR__ . '/../../bootstrap.php';

/**
 * Mock the original class.
 */
$Clients = new class('username', 'password') extends Clients
{
    public function __construct(string $username, string $password, string $session_dir = null)
    {
        $this->username    = $username;
        $this->password    = $password;
        $this->session_dir = TMP_DIR . DIRECTORY_SEPARATOR;

        $authentication = $this->authenticate();

        if (!$authentication) {
            throw new Pagalo\Error\Authentication('The given combination of username and password is incorrect');
        }
    }

    public function authenticate() : bool
    {
        $token = $this->getToken();

        return (!empty($this->username) && !empty($this->password) && !empty($token));
    }

    public function getAll() : array
    {
        return [
            (object) [
                'id'               => 16000,
                'id_empresa'       => 900,
                'nombre'           => 'John',
                'apellido'         => 'Smith',
                'email'            => 'john@amazingcompany.com',
                'direccion'        => '16192 Coastal Hwy, Lewes, DE 19958. United States of America.',
                'direccion_random' => null,
                'pais'             => 'US',
                'ciudad'           => 'Lewes',
                'estado'           => 1,
                'created_at'       => '2020-01-28 00:08:27',
                'telefono'         => '12345678',
                'idenEmpresa'      => 'A596951641',
                'state'            => '',
                'postalcode'       => '',
                'tipoCliente'      => 'PG',
                'nit'              => 'C/F',
            ]
        ];
    }

    public function search(string $client_name) : array
    {
        return array_merge($this->getAll(), ['busqueda' => $client_name]);
    }
};

/**
 * Clients::getAll() Test
 *
 * @assert type Check if the returned response it's an array.
 */
Assert::type('array', $Clients->getAll());

/**
 * Clients::get() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Clients->get(16000));
Assert::contains('john@amazingcompany.com', (array) $Clients->get(16000));

/**
 * Clients::search() Test
 *
 * @assert type Check if the returned response it's an array.
 */
Assert::type('array', $Clients->search('John'));

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
