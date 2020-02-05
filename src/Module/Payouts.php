<?php
/**
 * Payouts module.
 *
 * @package    Pagalo
 * @subpackage Pagalo.Module.Payouts
 * @copyright  Copyright (c) 2018-2020 Abdy Franco. All Rights Reserved.
 * @license    https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author     Abdy Franco <iam@abdyfran.co>
 */

namespace Pagalo\Module;

class Payouts extends \Pagalo\Pagalo
{
    /**
     * Get all the payouts.
     *
     * @return array An array containing all the payouts.
     */
    public function getAll() : ?array
    {
        $result = $this->sendRequest('api/mi/liquidaciones');

        return (array) isset($result->datos) ? $result->datos : null;
    }
}
