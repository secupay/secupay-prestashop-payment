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

class SecupayDisplayPaymentEUController
{
    /**
     * @var
     */
    public $module;
    /**
     * Constructor for Hook controller.
     *
     * @param $module
     * @param $file
     * @param $path
     */
    public function __construct($module, $file, $path)
    {
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
            'SecupayDisplayPaymentEUController:run',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        if (!$this->module->active) {
            return;
        }

        $ret = array();

        $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE');
        $pay_debit   = Configuration::get('SECUPAY_PAY_DEBIT');
        $pay_sofort  = Configuration::get('SECUPAY_PAY_SOFORT');
        $pay_cc      = Configuration::get('SECUPAY_PAY_CC');
        if ('EUR' === $this->context->currency->iso_code) {
            $currency = true;
        }

        PrestaShopLogger::addLog(
            'SecupayDisplayPaymentEUController:initContent',
            1,
            null,
            'Secupay Plugin',
            (int) $this->context->cart->id,
            true
        );
        if ($this->context->cart->id_address_delivery !== $this->context->cart->id_address_invoice) {
            $pay_invoice_secure = Configuration::get('SECUPAY_INVOICE_SECURE');
            $pay_debit_secure   = Configuration::get('SECUPAY_DEBIT_SECURE');
            $pay_sofort_secure  = Configuration::get('SECUPAY_SOFORT_SECURE');
            $pay_cc_secure      = Configuration::get('SECUPAY_CC_SECURE');

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

        if ($pay_cc && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Creditcard'),
                'logo'     => 'https://secupay.com/sites/default/files/media/Icons/' . $this->module->isSupportedLang()
                    . '/secupay_creditcard.png',
                'action'   => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'creditcard'),
                    true
                ),
            );
        }

        if ($pay_debit && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Debit'),
                'logo'     => 'https://secupay.com/sites/default/files/media/Icons/' . $this->module->isSupportedLang()
                    . '/secupay_debit.png',
                'action'   => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'debit'),
                    true
                ),
            );
        }

        if ($pay_invoice && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Invoice'),
                'logo'     => 'https://secupay.com/sites/default/files/media/Icons/' . $this->module->isSupportedLang()
                    . '/secupay_invoice.png',
                'action'   => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'invoice'),
                    true
                ),
            );
        }

        if ($pay_prepay && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay with secupay Prepay'),
                'logo'     => 'https://secupay.com/sites/default/files/media/Icons/' . $this->module->isSupportedLang()
                    . '/secupay_prepay.png',
                'action'   => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'prepay'),
                    true
                ),
            );
        }

        if ($pay_sofort && $currency) {
            $ret[] = array(
                'cta_text' => $this->module->l('Pay easy and secure with Pay now! transfer'),
                'logo'     => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/' .
                    $this->module->isSupportedLang() . '/pay_now/standard/pink.svg',
                'action'   => $this->context->link->getModuleLink(
                    $this->module->name,
                    'payment',
                    array('pt' => 'sofort'),
                    true
                ),
            );
        }

        return $ret;
    }
}
