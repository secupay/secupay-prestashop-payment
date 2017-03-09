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

class SecupayDisplayPaymentController
{
    /**
     * Constructor for Hook controller
     *
     * @param $module
     * @param $file
     * @param $path
     */
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    /**
     * Execute the hook
     *
     * @param $params
     * @return mixed
     */
    public function run()
    {
        $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE');
        $pay_debit = Configuration::get('SECUPAY_PAY_DEBIT');

        if ($this->context->cart->id_address_delivery !== $this->context->cart->id_address_invoice) {
            $pay_invoice_secure = Configuration::get('SECUPAY_INVOICE_SECURE');
            $pay_debit_secure = Configuration::get('SECUPAY_DEBIT_SECURE');

            $pay_invoice = !$pay_invoice_secure;
            $pay_debit = !$pay_debit_secure;
        }
        $pay_prepay = Configuration::get('SECUPAY_PAY_PREPAY');
        $pay_cc = Configuration::get('SECUPAY_PAY_CC');
        $addressInvoice = new Address((int)$this->context->cart->id_address_invoice);
        if(strpos(Configuration::get('SECUPAY_COUNTRY_CC'), $addressInvoice->country) === false && !empty(Configuration::get('SECUPAY_COUNTRY_CC')))
        {
          $pay_cc = 0;  
        }
        if(strpos(Configuration::get('SECUPAY_COUNTRY_DEBIT'), $addressInvoice->country) === false && !empty(Configuration::get('SECUPAY_COUNTRY_DEBIT')))
        {
          $pay_debit = 0;  
        }
        if(strpos(Configuration::get('SECUPAY_COUNTRY_INVOICE'), $addressInvoice->country) === false && !empty(Configuration::get('SECUPAY_COUNTRY_INVOICE')))
        {
          $pay_invoice = 0;  
        }
        if(strpos(Configuration::get('SECUPAY_COUNTRY_PREPAY'), $addressInvoice->country) === false && !empty(Configuration::get('SECUPAY_COUNTRY_PREPAY')))
        {
          $pay_prepay = 0;  
        }
        if ($this->context->currency->iso_code == 'EUR') {
            $this->context->smarty->assign(array(
                'pay_cc' => $pay_cc,
                'pay_invoice' => $pay_invoice,
                'pay_debit' => $pay_debit,
                'pay_prepay' => $pay_prepay,
                'this_path' => $this->_path,
                'this_path_bw' => $this->_path,
                'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ .
                'modules/' . $this->module->name . '/'
            ));
            $this->context->controller->addCSS($this->_path . 'views/css/secupay.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/secupay.js');
        }
        return $this->module->display($this->file, 'displayPayment.tpl');
    }
}
