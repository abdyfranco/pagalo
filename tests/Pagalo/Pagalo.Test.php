<?php
declare(strict_types = 1);

use Tester\Assert;
use Pagalo\Pagalo;

require __DIR__ . '/../bootstrap.php';

/**
 * Mock the original class.
 */
$Pagalo = new class('username', 'password') extends Pagalo
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

    public function getPlan() : ?\stdClass
    {
        return (object) [
            'configuracion' => (object) [
                'id'                 => 1,
                'id_plan'            => 1,
                'max_ventas_gtq'     => 50000,
                'max_ventas_usd'     => 5000,
                'max_productos'      => 1000,
                'max_usuarios'       => 100,
                'max_meses_busqueda' => 12,
                'liquidacion_semana' => 5
            ],
            'plan'          => (object) [
                'id'              => 10,
                'nombre'          => 'Premium',
                'monto_max'       => 999999,
                'estado'          => 1,
                'porcentaje'      => 5,
                'fee_monto'       => 0.25,
                'porcentaje_cybs' => 2.5,
                'tarifaGTQ'       => 0,
                'tarifaUSD'       => 10,
                'codigo'          => 'PRE'
            ]
        ];
    }
};

/**
 * Pagalo::sendRequest() Test
 *
 * @assert contains Check if the response contains the "Pagalo" word inside.
 */
Assert::contains('Pagalo', $Pagalo->sendRequest('login', [], 'GET', [], true));

/**
 * Pagalo::authenticate() Test
 *
 * @assert true Check if the function can execute the authentication process successfully.
 */
Assert::true($Pagalo->authenticate());

/**
 * Pagalo::getToken() Test
 *
 * @assert type Check if the authorization token can be fetched from the Pagalo system.
 */
Assert::type('string', $Pagalo->getToken());

/**
 * Pagalo::formatField() Test
 *
 * @assert equal Check if the function can format foreign characters correctly.
 */
Assert::equal('AAAEEEIIIOOOUUU', $Pagalo->formatField('ÀÃÅÈÉËÌÍÎÒÓÔÙÚÛ'));

/**
 * Pagalo::getUser() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Pagalo->getUser());
Assert::contains('john@awesomecompany.com', (array) $Pagalo->getUser());

/**
 * Pagalo::getCompany() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Pagalo->getCompany());
Assert::contains('Awesome Company, Inc.', (array) $Pagalo->getCompany());

/**
 * Pagalo::getPlan() Test
 *
 * @assert type Check if the returned response it's an object.
 */
Assert::type('object', $Pagalo->getPlan());

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
