<?php
/**
 * secupay Payment Module
 *
 * Copyright (c) 2016 secupay AG
 *
 * @category  Payment
 * @author    secupay AG
 * @copyright 2016, secupay AG
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

class Secupay extends PaymentModule
{
    public function __construct()
    {
        //require functions library
        require_once('lib/secupay_api.php');

        $this->name = 'secupay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'secupay AG';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;
        $this->controllers = array('payment', 'validation');
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->module_key = '6c563aa93cf1d5d06beab11f6f82a8bd';
        parent::__construct();

        $this->displayName = $this->l('secupay AG');
        $this->description = $this->l('Secupay - Easy Secure Payment - secupay AG is your reliable partner for online payment and payment processing in your online shop.');
        $this->confirmUninstall = $this->l('Are you want to delete the module with all the details?');
    }

    public function install()
    {
        if (!parent::install()
            || !Configuration::updateValue('SECUPAY_API')
            || !Configuration::updateValue('SECUPAY_DEMO')
            || !Configuration::updateValue('SECUPAY_PAY_CC')
            || !Configuration::updateValue('SECUPAY_PAY_DEBIT')
            || !Configuration::updateValue('SECUPAY_PAY_INVOICE')
            || !Configuration::updateValue('SECUPAY_PAY_PREPAY')
            || !Configuration::updateValue('SECUPAY_INVOICE_SECURE')
            || !Configuration::updateValue('SECUPAY_DEBIT_SECURE')
            || !Configuration::updateValue('SECUPAY_DEFAULT_ORDERSTATE')
            || !Configuration::updateValue('SECUPAY_SENDSHIPPINGAUTO')
            || !Configuration::updateValue('SECUPAY_SENDTRASHIPPINGAUTO')
            || !Configuration::updateValue('SECUPAY_SENDINVOICENUMBERAUTO')
            || !Configuration::updateValue('SECUPAY_SENDEXPERIENCEAUTO')
            || !Configuration::updateValue('SECUPAY_BLOCK_LOGO')
            || !Configuration::updateValue('SECUPAY_COUNTRY_CC')
            || !Configuration::updateValue('SECUPAY_COUNTRY_DEBIT')
            || !Configuration::updateValue('SECUPAY_COUNTRY_INVOICE')
            || !Configuration::updateValue('SECUPAY_COUNTRY_PREPAY')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayPayment')
            || !$this->registerHook('displayPaymentEU')
            || !$this->registerHook('leftColumn')
            || !$this->registerHook('ActionAdminOrdersTrackingNumberUpdate')
            || !$this->registerHook('displayPDFInvoice')
        ) {
            return false;
        }

        if (!$this->createTable()) {
            return false;
        }

        if (!$this->installOrderStates()) {
            return false;
        }

        // Set default configuration
        Configuration::updateValue('SECUPAY_DEFAULT_ORDERSTATE', Configuration::get('PS_OS_PREPARATION'));

        return true;
    }

    public function uninstall()
    {
        if (
            !Configuration::deleteByName('SECUPAY_API')
            || !Configuration::deleteByName('SECUPAY_DEMO')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_CC')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_DEBIT')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_INVOICE')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_PREPAY')
            || !Configuration::deleteByName('SECUPAY_PAY_CC')
            || !Configuration::deleteByName('SECUPAY_PAY_DEBIT')
            || !Configuration::deleteByName('SECUPAY_PAY_INVOICE')
            || !Configuration::deleteByName('SECUPAY_PAY_PREPAY')
            || !Configuration::deleteByName('SECUPAY_INVOICE_SECURE')
            || !Configuration::deleteByName('SECUPAY_DEBIT_SECURE')
            || !Configuration::deleteByName('SECUPAY_DEFAULT_ORDERSTATE')
            || !Configuration::deleteByName('SECUPAY_SENDSHIPPINGAUTO')
            || !Configuration::deleteByName('SECUPAY_SENDTRSSHIPPINGAUTO')
            || !Configuration::deleteByName('SECUPAY_SENDINVOICENUMBERAUTO')
            || !Configuration::deleteByName('SECUPAY_WAIT_FOR_CONFIRM')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_CONFIRMED')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_DENIED')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_ISSUE')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_VOID')
            || !Configuration::deleteByName('SECUPAY_SENDEXPERIENCEAUTO')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_AUTHORIZED')
            || !Configuration::deleteByName('SECUPAY_BLOCK_LOGO')
            || !$this->unregisterHook('displayPayment')
            || !$this->unregisterHook('displayPaymentEU')
            || !$this->unregisterHook('displayAdminOrder')
            || !$this->unregisterHook('paymentReturn')
            || !$this->unregisterHook('leftColumn')
            || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }

    public function processConfiguration()
    {
        if (Tools::isSubmit('secupay_pc_form')) {
            $apikey = trim(Tools::getValue('apikey'));
            $demo = Tools::getValue('demo');
            $pay_cc = Tools::getValue('pay_cc');
            $pay_debit = Tools::getValue('pay_debit');
            $pay_invoice = Tools::getValue('pay_invoice');
            $pay_prepay = Tools::getValue('pay_prepay');
            $invoice_secure = Tools::getValue('invoice_secure');
            $debit_secure = Tools::getValue('debit_secure');
            $default_orderstate = Tools::getValue('order_state');
            $sendshippingauto = Tools::getValue('sendshippingauto');
            $sendtrashippingauto = Tools::getValue('sendtrashippingauto');
            $sendinvoicenumberauto = Tools::getValue('sendinvoicenumberauto');
            $block_logo = Tools::getValue('block_logo');
            $sendexperienceauto = Tools::getValue('sendexperienceauto');
            $country_cc = Tools::getValue('country_cc');
            $country_debit = Tools::getValue('country_debit');
            $country_invoice = Tools::getValue('country_invoice');
            $country_prepay = Tools::getValue('country_prepay');

            if ($apikey === '') {
                Configuration::updateValue('SECUPAY_API', '{%SPAPIKEY%}');
            } else {
                Configuration::updateValue('SECUPAY_API', $apikey);
            }
            Configuration::updateValue('SECUPAY_DEMO', $demo);
            Configuration::updateValue('SECUPAY_PAY_CC', $pay_cc);
            Configuration::updateValue('SECUPAY_PAY_DEBIT', $pay_debit);
            Configuration::updateValue('SECUPAY_PAY_INVOICE', $pay_invoice);
            Configuration::updateValue('SECUPAY_PAY_PREPAY', $pay_prepay);
            Configuration::updateValue('SECUPAY_INVOICE_SECURE', $invoice_secure);
            Configuration::updateValue('SECUPAY_DEBIT_SECURE', $debit_secure);
            Configuration::updateValue('SECUPAY_DEFAULT_ORDERSTATE', $default_orderstate);
            Configuration::updateValue('SECUPAY_SENDSHIPPINGAUTO', $sendshippingauto);
            Configuration::updateValue('SECUPAY_SENDTRASHIPPINGAUTO', $sendtrashippingauto);
            Configuration::updateValue('SECUPAY_SENDINVOICENUMBERAUTO', $sendinvoicenumberauto);
            Configuration::updateValue('SECUPAY_BLOCK_LOGO', $block_logo);
            Configuration::updateValue('SECUPAY_SENDEXPERIENCEAUTO', $sendexperienceauto);
            Configuration::updateValue('SECUPAY_COUNTRY_CC', $country_cc);
            Configuration::updateValue('SECUPAY_COUNTRY_DEBIT', $country_debit);
            Configuration::updateValue('SECUPAY_COUNTRY_INVOICE', $country_invoice);
            Configuration::updateValue('SECUPAY_COUNTRY_PREPAY', $country_prepay);


            $this->context->smarty->assign(array(
                'confirmation' => 'ok'
            ));
        }
    }

    public function assignConfiguration()
    {
        $apikey = Configuration::get('SECUPAY_API');
        $country_cc = Configuration::get('SECUPAY_COUNTRY_CC');
        $country_debit = Configuration::get('SECUPAY_COUNTRY_DEBIT');
        $country_invoice = Configuration::get('SECUPAY_COUNTRY_INVOICE');
        $country_prepay = Configuration::get('SECUPAY_COUNTRY_PREPAY');
        $demo = Configuration::get('SECUPAY_DEMO');
        $sendshippingauto = Configuration::get('SECUPAY_SENDSHIPPINGAUTO');
        $sendtrashippingauto = Configuration::get('SECUPAY_SENDTRASHIPPINGAUTO');
        $sendinvoicenumberauto = Configuration::get('SECUPAY_SENDINVOICENUMBERAUTO');
        $block_logo = Configuration::get('SECUPAY_BLOCK_LOGO');
        $sendexperienceauto = Configuration::get('SECUPAY_SENDEXPERIENCEAUTO');
        $requestData = array();
        $requestData['data']['apikey'] = $apikey;
        $sp_api = new secupay_api($requestData, 'gettypes', 'application/json', '');
        $res = $sp_api->request();

        if (!Configuration::get('SECUPAY_PAY_INVOICE') ||
            !Configuration::get('SECUPAY_PAY_PREPAY') ||
            !Configuration::get('SECUPAY_PAY_CC') ||
            !Configuration::get('SECUPAY_PAY_DEBIT')
        ) {
            $pay_invoice = -99;
            $pay_cc = -99;
            $pay_debit = -99;
            $pay_prepay = -99;
            $pay_invoice_enabled = false;
            $pay_cc_enabled = false;
            $invoice_secure = false;
            $pay_debit_enabled = false;
            $pay_prepay_enabled = false;
            $debit_secure = false;
            $sendshippingauto = true;
            $sendtrashippingauto = true;
            $sendinvoicenumberauto = true;
            $sendexperienceauto = true;
            $block_logo = false;
        }

        if ($res->data) {
            in_array('invoice', $res->data)
                ? $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE')
                : $pay_invoice = -99;
            $pay_invoice_enabled = $pay_invoice != -99;

            in_array('creditcard', $res->data)
                ? $pay_cc = Configuration::get('SECUPAY_PAY_CC')
                : $pay_cc = -99;
            $pay_cc_enabled = $pay_cc != -99;
            $invoice_secure = Configuration::get('SECUPAY_INVOICE_SECURE');

            in_array('debit', $res->data)
                ? $pay_debit = Configuration::get('SECUPAY_PAY_DEBIT')
                : $pay_debit = -99;
            $pay_debit_enabled = $pay_debit != -99;
            $debit_secure = Configuration::get('SECUPAY_DEBIT_SECURE');

            in_array('prepay', $res->data)
                ? $pay_prepay = Configuration::get('SECUPAY_PAY_PREPAY')
                : $pay_prepay = -99;
            $pay_prepay_enabled = $pay_prepay != -99;
        }

        Configuration::get('SECUPAY_DEFAULT_ORDERSTATE') !== false
            ? $default_orderstate = Configuration::get('SECUPAY_DEFAULT_ORDERSTATE')
            : $default_orderstate = Configuration::get('PS_OS_PREPARATION');
        if ($pay_cc_enabled == true) {
            $pay_cc = true;
        } else {
            $pay_cc = false;
        }
        if ($pay_debit_enabled == true) {
            $pay_debit = true;
        } else {
            $pay_debit = false;
        }
        if ($pay_invoice_enabled == true) {
            $pay_invoice = true;
        } else {
            $pay_invoice = false;
        }
        if ($pay_prepay_enabled == true) {
            $pay_prepay = true;
        } else {
            $pay_prepay = false;
        }
        $this->context->smarty->assign(array(
            'apikey' => trim($apikey),
            'demo' => $demo,
            'pay_cc' => $pay_cc,
            'pay_cc_enabled' => $pay_cc_enabled,
            'pay_debit' => $pay_debit,
            'pay_debit_enabled' => $pay_debit_enabled,
            'pay_invoice' => $pay_invoice,
            'pay_invoice_enabled' => $pay_invoice_enabled,
            'pay_prepay' => $pay_prepay,
            'pay_prepay_enabled' => $pay_prepay_enabled,
            'invoice_secure' => $invoice_secure,
            'debit_secure' => $debit_secure,
            'sendshippingauto' => $sendshippingauto,
            'sendtrashippingauto' => $sendtrashippingauto,
            'sendinvoicenumberauto' => $sendinvoicenumberauto,
            'sendexperienceauto' => $sendexperienceauto,
            'block_logo' => $block_logo,
            'orderstates' => $this->getOrderStates($default_orderstate),
            'orderstate' => $default_orderstate,
            'country_cc' => trim($country_cc),
            'country_debit' => trim($country_debit),
            'country_invoice' => trim($country_invoice),
            'country_prepay' => trim($country_prepay)
        ));
    }

    public function getContent()
    {
        $this->processConfiguration();
        $this->assignConfiguration();
        return $this->display(__FILE__, 'configuration.tpl');
    }

    public function hookDisplayPayment($params)
    {
        // if (!$this->active)
        //      return;
        // if (!$this->checkCurrency($params['cart']))
        //      return;
        $controller = $this->getHookController('displayPayment');
        return $controller->run($params);
    }

    public function hookDisplayPaymentEU($params)
    {
        // if (!$this->active)
        //      return;
        // if (!$this->checkCurrency($params['cart']))
        //      return;
        $controller = $this->getHookController('displayPaymentEU');
        return $controller->run($params);
    }

    public function hookDisplayPaymentReturn($params)
    {
        $controller = $this->getHookController('displayPaymentReturn');
        return $controller->run($params);
    }

    public function hookDisplayPDFInvoice($params)
    {
        $controller = $this->getHookController('displayPDFInvoice');
        return $controller->run($params);
    }

    private function getHookController($hook_name)
    {
        require_once(dirname(__FILE__) . '/controllers/hook/' . $hook_name . '.php');
        $controller_name = $this->name . $hook_name . 'Controller';
        $controller = new $controller_name($this, __FILE__, $this->_path);
        return $controller;
    }


    private function installOrderStates()
    {
        if (configuration::get('SECUPAY_WAIT_FOR_CONFIRM') < 1) {
            $orderState = new OrderState();
            $orderState->name = $this->l('Waiting for confirmation from secupay');
            $orderState->invoice = false;
            $orderState->prepay = true;
            $orderState->send_email = false;
            $orderState->module_name = $this->name;
            $orderState->color = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = false;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = false;
            $orderState->template = 'cheque';

            $orderState->name = array(
                (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('Waiting for confirmation from secupay'))
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_WAIT_FOR_CONFIRM', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__file__))).
                        '/img/os/10.gif',
                        dirname(dirname(dirname(__file__))).
                        '/img/os/' .
                        $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_CONFIRMED') < 1) {
            $orderState = new OrderState();
            $orderState->name = $this->l('secupay payment confirmed');
            $orderState->invoice = true;
            $orderState->prepay = true;
            $orderState->send_email = true;
            $orderState->module_name = $this->name;
            $orderState->color = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = false;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = false;
            $orderState->template = 'payment';

            $orderState->name = array(
                (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment confirmed'))
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_CONFIRMED', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__file__))).
                        '/img/os/10.gif',
                        dirname(dirname(dirname(__file__))).
                        '/img/os/' .
                        $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_DENIED') < 1) {
            $orderState = new OrderState();
            $orderState->name = $this->l('secupay payment denied');
            $orderState->invoice = false;
            $orderState->send_email = false;
            $orderState->module_name = $this->name;
            $orderState->color = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = false;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = false;
            $orderState->template = 'payment_error';

            $orderState->name = array(
                (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment denied'))
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_DENIED', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__file__))).
                        '/img/os/10.gif',
                        dirname(dirname(dirname(__file__))).
                        '/img/os/' .
                        $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_ISSUE') < 1) {
            $orderState = new OrderState();
            $orderState->name = $this->l('secupay payment issue');
            $orderState->invoice = false;
            $orderState->send_email = false;
            $orderState->module_name = $this->name;
            $orderState->color = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = false;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = false;
            $orderState->template = 'payment_error';

            $orderState->name = array(
                (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment issue'))
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_ISSUE', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__file__))) .
                        '/img/os/10.gif',
                        dirname(dirname(dirname(__file__))) .
                        '/img/os/' .
                        $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_VOID') < 1) {
            $orderState = new OrderState();
            $orderState->name = $this->l('secupay payment void');
            $orderState->invoice = false;
            $orderState->send_email = false;
            $orderState->module_name = $this->name;
            $orderState->color = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = false;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = false;
            $orderState->template = 'payment_error';

            $orderState->name = array(
                (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment void'))
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_VOID', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__file__))) .
                        '/img/os/10.gif',
                        dirname(dirname(dirname(__file__))) .
                        '/img/os/' .
                        $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_AUTHORIZED') < 1) {
            $orderState = new OrderState();
            $orderState->name = $this->l('secupay payment authorized');
            $orderState->invoice = false;
            $orderState->prepay = false;
            $orderState->send_email = false;
            $orderState->module_name = $this->name;
            $orderState->color = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden = false;
            $orderState->logable = false;
            $orderState->delivery = false;
            $orderState->shipped = false;
            $orderState->paid = false;
            $orderState->template = 'payment';

            $orderState->name = array(
                (int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment authorized'))
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_AUTHORIZED', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__file__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__file__))) .
                        '/img/os/10.gif',
                        dirname(dirname(dirname(__file__))) .
                        '/img/os/' .
                        $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function deleteOrderState()
    {
        $orderState = new OrderState((int)Configuration::get('SECUPAY_WAIT_FOR_CONFIRM'));
        $orderState->delete();
        $orderState = new OrderState((int)Configuration::get('SECUPAY_PAYMENT_CONFIRMED'));
        $orderState->delete();
        $orderState = new OrderState((int)Configuration::get('SECUPAY_PAYMENT_DENIED'));
        $orderState->delete();
        $orderState = new OrderState((int)Configuration::get('SECUPAY_PAYMENT_ISSUE'));
        $orderState->delete();
        $orderState = new OrderState((int)Configuration::get('SECUPAY_PAYMENT_VOID'));
        $orderState->delete();
        $orderState = new OrderState((int)Configuration::get('SECUPAY_PAYMENT_AUTHORIZED'));
        $orderState->delete();
        $orderState = new OrderState((int)Configuration::get('SECUPAY_PAY_DEBIT'));
        $orderState->delete();
    }

    private function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "secupay`(
	    `id_secupay` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `req_data` TEXT NOT NULL,
        `ret_data` TEXT NOT NULL,
        `payment_type` varchar(255) NOT NULL,
        `hash` varchar(255) NOT NULL ,
        `unique_id` varchar(255) default NULL,
	    `id_order` varchar(50) default NULL,
        `trans_id` int(10) UNSIGNED default 0,
        `msg` varchar(255) default NULL,
        `rank` int(10) UNSIGNED default 0,
        `status` varchar(255) default NULL,
        `amount` varchar(255) default NULL,
        `updated` datetime default NULL,
        `created` datetime default NULL,
        `timestamp` TIMESTAMP NOT NULL,
        `apikey` varchar(64) NOT NULL,
        `v_status` varchar(20) default NULL,
        `v_send` varchar(1) default 0,
        `track_number` varchar(255) default NULL,
        `track_send` varchar(1) default NULL,
        `carrier_code` varchar(32) default NULL,
        `searchcode` varchar(255) default NULL
        )";

        if (!Db::getInstance()->Execute($sql)) {
            return false;
        }

        return true;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function setMedia()
    {
        parent::setMedia();
        $this->path = __PS_BASE_URI__ . 'modules/secupay/';
        $this->context->controller->addCSS($this->path . 'views/css/secupay.css', 'all');
    }

    public function isPaymentTypeOkay($pt)
    {
        return $pt == 'invoice' || $pt == 'creditcard' || $pt == 'debit' || $pt == 'prepay';
    }

    private function getOrderStates($selected = null)
    {
        $order_states = OrderState::getOrderStates((int)$this->context->cookie->id_lang);

        $result = '';
        foreach ($order_states as $state) {
            $result .= '<option value="' . $state['id_order_state'] . '" ';
            $result .= ($state['id_order_state'] == $selected ? 'selected="selected"' : '');
            $result .= '>' . $state['name'] . '</option>';
        }
        return $order_states;
    }
}
