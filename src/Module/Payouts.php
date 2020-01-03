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
    public function getAll()
    {
        $result = $this->sendRequest('api/mi/liquidaciones');
        $result = json_decode($result);

        return isset($result->datos) ? $result->datos : null;
    }
}
