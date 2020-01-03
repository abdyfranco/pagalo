<?php
/**
 * Products module.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Module.Products
 * @copyright  Copyright (c) 2018-2020 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Module;

class Products extends \Pagalo\Pagalo
{
    public function getAll()
    {
        // Get company products
        $result = $this->sendRequest('api/mi/productos');
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }

    public function search($product_name)
    {
        $params  = [
            'dato'     => trim($product_name),
            'busqueda' => trim($product_name),
        ];
        $result  = $this->sendRequest('api/miV2/searchProduct', $params, 'POST', [
            'Content-Type: application/json;charset=UTF-8'
        ]);
        $result  = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }
}
