<?php
declare(strict_types = 1);

use Tester\Assert;
use Pagalo\Module\Payments;

require __DIR__ . '/../../bootstrap.php';

/**
 * Mock the original class.
 */
$Payments = new class('username', 'password') extends Payments
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

    public function getDeviceFingerprint() : int
    {
        return (int) round(microtime(true) * 1000);
    }

    public function getUser() : ?\stdClass
    {
        return (object) [
            'id'         => 2000,
            'name'       => 'John Smith',
            'email'      => 'john@awesomecompany.com',
            'created_at' => '2019-10-20 11:50:29',
            'updated_at' => '2019-10-20 11:50:29',
            'empresa'    => (object) [
                'id'           => 932,
                'id_user'      => 2208,
                'nombre'       => 'Awesome Company, Inc.',
                'id_categoria' => 5,
                'ubicacion'    => '16192 Coastal Hwy, Lewes, DE 19958. United States of America.'
            ]
        ];
    }

    public function getAll() : array
    {
        return [
            (object) [
                'id_empresa'     => 900,
                'idenEmpresa'    => 'A596951641',
                'id_user'        => 2208,
                'id_transaccion' => 45000,
                'email'          => 'john@awesomecompany.com',
                'Total'          => 1000,
                'token'          => 'ZXJndGg0NHdudDRydG50cndydG5uZHM0MzVuZ2ZudHJ0d2V0cmhlcnRocWUr',
                'estado'         => 2,
                'created_at'     => '2020-02-01 15:30:00',
                'id'             => 12000,
                'url_short'      => 'https://bit.ly/2OsGaxj',
                'tipo_solicitud' => 2,
                'tipoPago'       => 'CY'
            ]
        ];
    }

    public function request(int $client_id, string $description, float $amount, string $currency = 'USD') : ?stdClass
    {
        return (object) [
            'id_transaccion' => 45000,
            'cliente'        => [
                'id'               => 9000,
                'id_empresa'       => 932,
                'nombre'           => 'John',
                'apellido'         => 'Smith',
                'email'            => 'john@awesomecompany.com',
                'direccion'        => '16192 Coastal Hwy, Lewes, DE 19958. United States of America.',
                'direccion_random' => null,
                'pais'             => 'US',
                'ciudad'           => 'Lewes',
                'estado'           => 1,
                'created_at'       => '2019-08-10 22:33:13',
                'telefono'         => '12345678',
                'idenEmpresa'      => 'A596951641',
                'state'            => null,
                'postalcode'       => null,
                'tipoCliente'      => 'PG',
                'nit'              => 'C/F',
                'id_cliente'       => 9000,
                'tipoTransac'      => 'S'
            ],
            'id_solicitud'   => 12000,
            'url'            => 'https://bit.ly/2OsGaxj'
        ];
    }

    public function process(int $client_id, \Pagalo\Object\Card $card, string $description, float $amount, string $currency = 'USD') : ?stdClass
    {
        return (object) [
            'id_transaccion' => 40000,
            'cliente'        => [
                'id'               => 13000,
                'id_empresa'       => 900,
                'nombre'           => 'John',
                'apellido'         => 'Smith',
                'email'            => 'john@awesomecompany.com',
                'direccion'        => '16192 Coastal Hwy, Lewes, DE 19958. United States of America.',
                'direccion_random' => null,
                'pais'             => 'US',
                'ciudad'           => 'Lewes',
                'estado'           => 1,
                'created_at'       => '2019-11-29 10:19:49',
                'telefono'         => '12345678',
                'idenEmpresa'      => 'A596951641',
                'state'            => null,
                'postalcode'       => null,
                'tipoCliente'      => 'PG',
                'nit'              => 'C/F',
                'id_cliente'       => 13000,
                'tipoTransac'      => 'S'
            ],
            'decision'       => 'ACCEPT',
            'reasonCode'     => 100,
            'requestID'      => '5894586890346084633309'
        ];
    }
};

/**
 * Payments::getDeviceFingerprint() Test
 *
 * @assert type Check if the returned response it's an integer.
 */
Assert::type('int', $Payments->getDeviceFingerprint());

/**
 * Payments::getAll() Test
 *
 * @assert type Check if the returned response it's an array.
 */
Assert::type('array', $Payments->getAll());

/**
 * Payments::get() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Payments->get(45000));
Assert::contains('https://bit.ly/2OsGaxj', (array) $Payments->get(45000));

/**
 * Payments::request() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Payments->request(9000, 'Payment', 100));
Assert::contains('https://bit.ly/2OsGaxj', (array) $Payments->request(9000, 'Payment', 100));

/**
 * Payments::process() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Payments->process(9000, new \Pagalo\Object\Card(
    '4242424242424242',
    'John Smith',
    '12/2025',
    '045'
), 'Payment', 100));
Assert::contains('ACCEPT', (array) $Payments->process(9000, new \Pagalo\Object\Card(
    '4242424242424242',
    'John Smith',
    '12/2025',
    '045'
), 'Payment', 100));

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
