<?php
declare(strict_types = 1);

use Tester\Assert;
use Pagalo\Module\Products;

require __DIR__ . '/../../bootstrap.php';

/**
 * Mock the original class.
 */
$Products = new class('username', 'password') extends Products
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
                'id'          => 5000,
                'id_empresa'  => 900,
                'nombre'      => 'Amazing Product',
                'descripcion' => 'Lorem ipsum',
                'categoria'   => 1000,
                'precio'      => 100,
                'estado'      => 1,
                'precio_usd'  => 20,
                'stock'       => 0,
                'sku'         => 'AMZNG',
                'imagen'      => '',
                'ac_stock'    => 0,
                'url_short'   => 'https://app.pagalocard.com/t/amazing-company?producto=5000',
                'hora_inicio' => '00:00:00',
                'hora_fin'    => '00:00:00',
                'variantes'   => []
            ]
        ];
    }

    public function search(string $product_name) : array
    {
        return array_merge($this->getAll(), ['busqueda' => $product_name]);
    }
};

/**
 * Products::getAll() Test
 *
 * @assert type Check if the returned response it's an array.
 */
Assert::type('array', $Products->getAll());

/**
 * Products::get() Test
 *
 * @assert type Check if the returned response it's an object.
 * @assert contains Check if the returned object has the expected properties.
 */
Assert::type('object', $Products->get(5000));
Assert::contains('Amazing Product', (array) $Products->get(5000));

/**
 * Products::search() Test
 *
 * @assert type Check if the returned response it's an array.
 */
Assert::type('array', $Products->search('Amazing'));

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
