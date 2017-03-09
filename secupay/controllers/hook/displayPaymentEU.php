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

class SecupayDisplayPaymentEUController
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
        if (!$this->module->active) {
            return;
        }

        $ret = array();

        $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE');
        $pay_debit = Configuration::get('SECUPAY_PAY_DEBIT');
        if ($this->context->currency->iso_code == 'EUR') {
            $currency = true;
        }
        
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
        if ($pay_cc && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Creditcard'),
                'logo' => 'https://www.secupay.ag/sites/default/files/media/Icons/secupay_creditcard.png',
                'action' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'creditcard'),
                    true
                )
            );
        };

        if ($pay_debit && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Debit'),
                'logo' => 'https://www.secupay.ag/sites/default/files/media/Icons/secupay_debit.png',
                'action' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'debit'),
                    true
                )
            );
        };

        if ($pay_invoice && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Invoice'),
                'logo' => 'https://www.secupay.ag/sites/default/files/media/Icons/secupay_invoice.png',
                'action' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'invoice'),
                    true
                )
            );
        };

        if ($pay_prepay && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Prepay'),
                'logo' => 'https://www.secupay.ag/sites/default/files/media/Icons/secupay_prepay.png',
                'action' => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'prepay'),
                    true
                )
            );
        };
        return $ret;
    }
}
