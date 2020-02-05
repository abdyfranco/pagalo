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

use stdClass;

class Products extends \Pagalo\Pagalo
{
    /**
     * Get all products.
     *
     * @return array An array containing all the products on the store.
     */
    public function getAll() : array
    {
        // Get company products
        $result = $this->sendRequest('api/mi/productos');

        return (array) isset($result->datos) ? $result->datos : null;
    }

    /**
     * Get a product.
     *
     * @param int $product_id The product ID.
     *
     * @return null|\stdClass An object containing the product information.
     */
    public function get(int $product_id) : ?stdClass
    {
        // Get all company products
        $products = $this->getAll();

        // Search the required payment
        foreach ($products as $product) {
            if ($product->id == $product_id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Search a product.
     *
     * @param string $product_name The product name to search.
     *
     * @return array An array containing all the matches for the search.
     */
    public function search(string $product_name) : array
    {
        $params = [
            'dato'     => trim($product_name),
            'busqueda' => trim($product_name),
        ];
        $result = $this->sendRequest(
            'api/miV2/searchProduct',
            $params,
            'POST',
            [
                'Content-Type: application/json;charset=UTF-8'
            ]
        );

        return (array) isset($result->datos) ? $result->datos : null;
    }
}
