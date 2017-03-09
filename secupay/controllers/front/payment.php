<?php
/**
 * secupay Payment Module
 *
 * Copyright (c) 2015 secupay AG
 *
 * @category  Payment
 * @author    secupay AG
 * @copyright 2015, secupay AG
 * @link      https://www.secupay.ag/de/online-commerce/shopmodule
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License Version 2.0
 *
 * Description:
 *
 * PrestaShop module for integration of secupay AG payment services
 *
 * --
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class SecupayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;
    public $display_column_right = false;

    /**
     * Show payment overview
     */
    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;
        // $this->addCSS($this->_path.'views/css/secupay.css', 'all');
        //  $this->addJS($this->_path.'views/js/secupay.js');

        parent::initContent();

        $this->context->smarty->assign(array(
            'this_path' => $this->module->getPathUri(),
            'this_path_bw' => $this->module->getPathUri(),
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
            'modules/' . $this->module->name . '/',
            'nb_products' => $this->context->cart->nbProducts(),
            'cart_currency' => $this->context->cart->id_currency,
            'currencies' => $this->module->getCurrency((int)$this->context->cart->id_currency),
            'total_amount' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
            'path' => $this->module->getPathUri(),
            'pt' => Tools::getValue('pt')
        ));

        $this->setTemplate('payment.tpl');
    }
}
