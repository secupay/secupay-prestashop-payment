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

class SecupayDisplayPaymentOptionsController
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
        PrestaShopLogger::addLog(
            'SecupayDisplayPaymentOptionsController:__construct',
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
     * return array(
     * $paymentOption1 ,
     * $paymentOption2
     * );
     *
     * @return mixed
     */
    public function run()
    {
        PrestaShopLogger::addLog(
            'SecupayDisplayPaymentOptionsController:run',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        if (!$this->module->active) {
            return;
        }
        $ret         = array();
        $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE');
        $pay_debit   = Configuration::get('SECUPAY_PAY_DEBIT');
        $pay_sofort  = Configuration::get('SECUPAY_PAY_SOFORT');
        $pay_cc      = Configuration::get('SECUPAY_PAY_CC');
        if ('EUR' === $this->context->currency->iso_code) {
            $currency = true;
        }
        if ($this->context->cart->id_address_delivery !== $this->context->cart->id_address_invoice) {
            $pay_invoice_secure = Configuration::get('SECUPAY_INVOICE_SECURE');
            $pay_debit_secure   = Configuration::get('SECUPAY_DEBIT_SECURE');
            $pay_sofort_secure  = Configuration::get('SECUPAY_SOFORT_SECURE');
            $pay_cc_secure      = Configuration::get('SECUPAY_CC_SECURE');
            $pay_invoice        = !$pay_invoice_secure;
            $pay_debit          = !$pay_debit_secure;
            $pay_cc             = !$pay_cc_secure;
            $pay_sofort         = !$pay_sofort_secure;
        }
        $pay_prepay     = Configuration::get('SECUPAY_PAY_PREPAY');
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
            PrestaShopLogger::addLog(
                'SecupayDisplayPaymentOptionsController:cc',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $paymentOptionCC = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOptionCC->setCallToActionText(
                $this->module->l('Pay with secupay Creditcard', 'displayPaymentOptions')
            )
                            ->setAction(
                                $this->context->link->getModuleLink(
                                    $this->module->name,
                                    'payment',
                                    array('pt' => 'creditcard'),
                                    true
                                )
                            )
                            ->setAdditionalInformation(
                                $this->context->smarty->assign(
                                    'mod_lang',
                                    array(
                                        'logo' => 'https://secupay.com/sites/default/files/media/Icons/'
                                            . $this->module->isSupportedLang() . '/secupay_creditcard.png',
                                        'link' => 'https://secupay.com',
                                        'desc' => $this->module->l(
                                            'Pay easily and securely with your credit card.',
                                            'displayPaymentOptions'
                                        ),
                                    )
                                )
                            )
                            ->setAdditionalInformation(
                                $this->context->smarty->fetch(
                                    'module:secupay/views/templates/front/payment_options.tpl'
                                )
                            );
            $ret[] = $paymentOptionCC;
        }
        if ($pay_debit && $currency) {
            PrestaShopLogger::addLog(
                'SecupayDisplayPaymentOptionsController:debit',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $paymentOptionDebit = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOptionDebit->setCallToActionText(
                $this->module->l('Pay with secupay Debit', 'displayPaymentOptions')
            )
                               ->setAction(
                                   $this->context->link->getModuleLink(
                                       $this->module->name,
                                       'payment',
                                       array('pt' => 'debit'),
                                       true
                                   )
                               )
                               ->setAdditionalInformation(
                                   $this->context->smarty->assign(
                                       'mod_lang',
                                       array(
                                           'logo' => 'https://secupay.com/sites/default/files/media/Icons/'
                                               . $this->module->isSupportedLang() . '/secupay_debit.png',
                                           'link' => 'https://secupay.com',
                                           'desc' => $this->module->l(
                                               'You pay comfortably by debit.',
                                               'displayPaymentOptions'
                                           ),
                                       )
                                   )
                               )
                               ->setAdditionalInformation(
                                   $this->context->smarty->fetch(
                                       'module:secupay/views/templates/front/payment_options.tpl'
                                   )
                               );
            $ret[] = $paymentOptionDebit;
        }
        if ($pay_invoice && $currency) {
            PrestaShopLogger::addLog(
                'SecupayDisplayPaymentOptionsController:invoice',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $paymentOptionInvoice = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOptionInvoice->setCallToActionText(
                $this->module->l('Pay with secupay Invoice', 'displayPaymentOptions')
            )
                                 ->setAction(
                                     $this->context->link->getModuleLink(
                                         $this->module->name,
                                         'payment',
                                         array('pt' => 'invoice'),
                                         true
                                     )
                                 )
                                 ->setAdditionalInformation(
                                     $this->context->smarty->assign(
                                         'mod_lang',
                                         array(
                                             'logo' => 'https://secupay.com/sites/default/files/media/Icons/'
                                                 . $this->module->isSupportedLang() . '/secupay_invoice.png',
                                             'link' => 'https://secupay.com',
                                             'desc' => $this->module->l(
                                                 'Pay the amount upon receipt and examination of goods.',
                                                 'displayPaymentOptions'
                                             ),
                                         )
                                     )
                                 )
                                 ->setAdditionalInformation(
                                     $this->context->smarty->fetch(
                                         'module:secupay/views/templates/front/payment_options.tpl'
                                     )
                                 );
            $ret[] = $paymentOptionInvoice;
        }
        if ($pay_prepay && $currency) {
            PrestaShopLogger::addLog(
                'SecupayDisplayPaymentOptionsController:prepay',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $paymentOptionPepay = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOptionPepay->setCallToActionText(
                $this->module->l('Pay with secupay Prepay', 'displayPaymentOptions')
            )
                               ->setAction(
                                   $this->context->link->getModuleLink(
                                       $this->module->name,
                                       'payment',
                                       array('pt' => 'prepay'),
                                       true
                                   )
                               )
                               ->setAdditionalInformation(
                                   $this->context->smarty->assign(
                                       'mod_lang',
                                       array(
                                           'logo' => 'https://secupay.com/sites/default/files/media/Icons/'
                                               . $this->module->isSupportedLang() . '/secupay_prepay.png',
                                           'link' => 'https://secupay.com',
                                           'desc' => $this->module->l(
                                               'You pay in advance and get your ordered goods after money is received.',
                                               'displayPaymentOptions'
                                           ),
                                       )
                                   )
                               )
                               ->setAdditionalInformation(
                                   $this->context->smarty->fetch(
                                       'module:secupay/views/templates/front/payment_options.tpl'
                                   )
                               );
            $ret[] = $paymentOptionPepay;
        }
        if ($pay_sofort && $currency) {
            PrestaShopLogger::addLog(
                'SecupayDisplayPaymentOptionsController:sofort',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $paymentOptionSofort = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
            $paymentOptionSofort->setCallToActionText(
                $this->module->l('Pay easy and secure with Pay now! transfer', 'displayPaymentOptions')
            )
                                ->setAction(
                                    $this->context->link->getModuleLink(
                                        $this->module->name,
                                        'payment',
                                        array('pt' => 'sofort'),
                                        true
                                    )
                                )
                                ->setAdditionalInformation(
                                    $this->context->smarty->assign(
                                        'mod_lang',
                                        array(
                                            'logo' => 'https://cdn.klarna.com/1.0/shared/image/generic/badge/'
                                                . $this->module->isSupportedLang() . '/pay_now/standard/pink.svg',
                                            'link' => 'https://secupay.com',
                                            'desc' => $this->module->l(
                                                'You pay easily and directly with Online Banking.',
                                                'displayPaymentOptions'
                                            ),
                                        )
                                    )
                                )
                                ->setAdditionalInformation(
                                    $this->context->smarty->fetch(
                                        'module:secupay/views/templates/front/payment_options.tpl'
                                    )
                                );
            $ret[] = $paymentOptionSofort;
        }
        return $ret;
    }
}
