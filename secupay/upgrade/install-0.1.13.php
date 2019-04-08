<?php
/**
 * secupay Payment Module.
 *
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 *
 * @category  Payment
 *
 * Description:
 *  Prestashop Plugin for integration of secupay AG payment services
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param $module
 *
 * @return mixed
 */
function upgrade_module_0_1_13($module)
{
    return $module->unregisterHook('paymentReturn');
}
