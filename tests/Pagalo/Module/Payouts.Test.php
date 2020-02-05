<?php
declare(strict_types = 1);

use Tester\Assert;
use Pagalo\Module\Payouts;

require __DIR__ . '/../../bootstrap.php';

/**
 * Mock the original class.
 */
$Payouts = new class('username', 'password') extends Payouts
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
                'id'                   => 4000,
                'id_empresa'           => 900,
                'idenEmpresa'          => 'A59493049',
                'fecha_inicio'         => '2019-10-10 00:00:00',
                'fecha_fin'            => '2019-10-10 00:00:00',
                'fecha_liquidacion'    => '2019-10-10 00:00:00',
                'moneda'               => 'USD',
                'monto_neto'           => 90,
                'monto_base'           => 80.3571,
                'comision_cybs'        => 2.01,
                'comision_pagalo'      => 5.22,
                'ivacomision_cybs'     => 0.2411,
                'ivacomision_pagalo'   => 0.6268,
                'retencioniva_cybs'    => 1.4464,
                'retencioniva_pagalo'  => 1.4464,
                'fee_montodolar'       => 0,
                'fee_monto'            => 0.25,
                'subtotalcomi_cybs'    => 3.9464,
                'subtotalcomi_pagalo'  => 7.5464,
                'deposito_cybs'        => 86.0536,
                'deposito_empresa'     => 82.4536,
                'deposito_empresar'    => 83.9,
                'monto_contracargo'    => 0,
                'deposito_banco'       => 0,
                'utilidad_transaccion' => 2.1536,
                'realizado_por'        => 0,
                'estado'               => 2,
                'total_transaccion'    => 1,
                'cobro_seguridad'      => 0.25,
                'observaciones'        => 'Referencia: 826365942',
                'banco_liquidador'     => 'Awesome Bank',
                'id_liquidacion'       => 0,
                'tipo_liquidacion'     => 'Venta',
                'pais_operador'        => 'Guatemala',
                'id_factura'           => 400
            ]
        ];
    }
};

/**
 * Payouts::getAll() Test
 *
 * @assert type Check if the returned response it's an array.
 */
Assert::type('array', $Payouts->getAll());

/**
 * Clear all temporary files
 */
@rmdir(__DIR__ . '/output');
@rmdir(TMP_DIR);
