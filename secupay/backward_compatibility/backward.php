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

/**
 * Backward function compatibility
 * Need to be called for each module in 1.4.
 */

// Get out if the context is already defined
if (!in_array(
    'Context',
    get_declared_classes()
)) {
    require_once dirname(__FILE__) . '/Context.php';
}

// Get out if the Display (BWDisplay to avoid any conflict)) is already defined
if (!in_array(
    'BWDisplay',
    get_declared_classes()
)) {
    require_once dirname(__FILE__) . '/Display.php';
}

// If not under an object we don't have to set the context
if (!isset($this)) {
    return;
} elseif (isset($this->context)) {
    // If we are under an 1.5 version and backoffice, we have to set some backward variable
    if (_PS_VERSION_ >= '1.5' && isset($this->context->employee->id) && $this->context->employee->id
        && isset(AdminController::$currentIndex)
        && !empty(AdminController::$currentIndex)) {
        global $currentIndex;
        $currentIndex = AdminController::$currentIndex;
    }

    return;
}

$this->context = Context::getContext();
$this->smarty  = $this->context->smarty;
