<?php
/**
 * secupay Payment Module
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 * @category  Payment
 *
 * Description:
 *  Prestashop Plugin for integration of secupay AG payment services
 */

class SecupayDisplayPaymentController
{
    /**
     * Constructor for Hook controller.
     *
     * @param $module
     * @param $file ~~~
     * @param $path
     */
    public function __construct($module, $file, $path)
    {
        PrestaShopLogger::addLog(
            'SecupayDisplayPaymentController:__construct',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $this->file    = $file;
        $this->module  = $module;
        $this->context = Context::getContext();
        $this->_path   = $path;
    }

    /**
     * Execute the hook.
     *
     * @return mixed
     */
    public function run()
    {
        PrestaShopLogger::addLog(
            'SecupayDisplayPaymentController:run',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE');
        $pay_debit   = Configuration::get('SECUPAY_PAY_DEBIT');
        $pay_cc      = Configuration::get('SECUPAY_PAY_CC');
        $pay_sofort  = Configuration::get('SECUPAY_PAY_SOFORT');
        if ($this->context->cart->id_address_delivery !== $this->context->cart->id_address_invoice) {
            $pay_invoice_secure = Configuration::get('SECUPAY_INVOICE_SECURE');
            $pay_debit_secure   = Configuration::get('SECUPAY_DEBIT_SECURE');
            $pay_cc_secure      = Configuration::get('SECUPAY_CC_SECURE');
            $pay_sofort_secure  = Configuration::get('SECUPAY_SOFORT_SECURE');

            $pay_invoice = !$pay_invoice_secure;
            $pay_debit   = !$pay_debit_secure;
            $pay_cc      = !$pay_cc_secure;
            $pay_sofort  = !$pay_sofort_secure;
        }
        $pay_prepay = Configuration::get('SECUPAY_PAY_PREPAY');

        $addressInvoice = new Address((int) $this->context->cart->id_address_invoice);
        if (false === strpos(Configuration::get('SECUPAY_COUNTRY_CC'), $addressInvoice->country)
            && !empty(Configuration::get('SECUPAY_COUNTRY_CC'))) {
            $pay_cc = 0;
        }
        if (false === strpos(Configuration::get('SECUPAY_COUNTRY_DEBIT'), $addressInvoice->country)
            && !empty(Configuration::get('SECUPAY_COUNTRY_DEBIT'))) {
            $pay_debit = 0;
        }
        if (false === strpos(Configuration::get('SECUPAY_COUNTRY_INVOICE'), $addressInvoice->country)
            && !empty(Configuration::get('SECUPAY_COUNTRY_INVOICE'))) {
            $pay_invoice = 0;
        }
        if (false === strpos(Configuration::get('SECUPAY_COUNTRY_PREPAY'), $addressInvoice->country)
            && !empty(Configuration::get('SECUPAY_COUNTRY_PREPAY'))) {
            $pay_prepay = 0;
        }
        if (false === strpos(Configuration::get('SECUPAY_COUNTRY_SOFORT'), $addressInvoice->country)
            && !empty(Configuration::get('SECUPAY_COUNTRY_SOFORT'))) {
            $pay_sofort = 0;
        }
        if ('EUR' === $this->context->currency->iso_code) {
            $this->context->smarty->assign(
                array(
                    'lang'          => $this->module->isSupportedLang(),
                    'pay_cc'        => $pay_cc,
                    'pay_invoice'   => $pay_invoice,
                    'pay_debit'     => $pay_debit,
                    'pay_prepay'    => $pay_prepay,
                    'pay_sofort'    => $pay_sofort,
                    'this_path'     => $this->_path,
                    'this_path_bw'  => $this->_path,
                    'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/'
                        . $this->module->name . '/',
                )
            );
            $this->context->controller->addCSS(
                $this->_path . 'views/css/secupay.css',
                'all'
            );
            $this->context->controller->addJS($this->_path . 'views/js/secupay.js');
        }

        return $this->module->display(
            $this->file,
            'displayPayment.tpl'
        );
    }
}
