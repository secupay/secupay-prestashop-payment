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

class Secupay extends PaymentModule
{
    /**
     * @var array
     */
    private $languages = array(
        'en' => 'en_us',
        'gb' => 'en_us',
        'de' => 'de_de',
    );

    /**
     * Secupay constructor.
     */
    public function __construct()
    {
        //require functions library
        require_once 'lib/secupay_api.php';
        $this->name                   = 'secupay';
        $this->tab                    = 'payments_gateways';
        $this->version                = '0.2.00';
        $this->author                 = 'secupay AG';
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';
        $this->is_eu_compatible       = 1;
        $this->bootstrap              = true;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->module_key             = '6c563aa93cf1d5d06beab11f6f82a8bd';
        $this->controllers            = array(
            'payment',
            'validate'
        );
        parent::__construct();
        $this->page             = basename(__FILE__, '.php');
        $this->displayName      = $this->l('secupay AG');
        $this->description      = $this->l('secupay AG is your reliable partner for online payment.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
        if (!isset($this->context->smarty->registered_plugins['function']['displayPrice'])) {
            smartyRegisterFunction(
                $this->context->smarty,
                'function',
                'displayPrice',
                array(
                    'Tools',
                    'displayPriceSmarty',
                )
            );
        }
    }

    /**
     * Install module.
     *
     * @see PaymentModule::install()
     */
    public function install()
    {
        PrestaShopLogger::addLog(
            'Secupay:install',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        if (!parent::install()
            || !Configuration::updateValue('SECUPAY_API', '')
            || !Configuration::updateValue('SECUPAY_DEMO', '')
            || !Configuration::updateValue('SECUPAY_COUNTRY_CC', '')
            || !Configuration::updateValue('SECUPAY_COUNTRY_DEBIT', '')
            || !Configuration::updateValue('SECUPAY_COUNTRY_INVOICE', '')
            || !Configuration::updateValue('SECUPAY_COUNTRY_PREPAY', '')
            || !Configuration::updateValue('SECUPAY_COUNTRY_SOFORT', '')
            || !Configuration::updateValue('SECUPAY_PAY_CC', '')
            || !Configuration::updateValue('SECUPAY_PAY_DEBIT', '')
            || !Configuration::updateValue('SECUPAY_PAY_INVOICE', '')
            || !Configuration::updateValue('SECUPAY_PAY_PREPAY', '')
            || !Configuration::updateValue('SECUPAY_PAY_SOFORT', '')
            || !Configuration::updateValue('SECUPAY_INVOICE_SECURE', '')
            || !Configuration::updateValue('SECUPAY_DEBIT_SECURE', '')
            || !Configuration::updateValue('SECUPAY_CC_SECURE', '')
            || !Configuration::updateValue('SECUPAY_SOFORT_SECURE', '')
            || !Configuration::updateValue('SECUPAY_DEFAULT_ORDERSTATE', '')
            || !Configuration::updateValue('SECUPAY_SENDINVOICENUMBERAUTO', '')
            || !Configuration::updateValue('SECUPAY_WAIT_FOR_CONFIRM', '')
            || !Configuration::updateValue('SECUPAY_PAYMENT_CONFIRMED', '')
            || !Configuration::updateValue('SECUPAY_PAYMENT_DENIED', '')
            || !Configuration::updateValue('SECUPAY_PAYMENT_ISSUE', '')
            || !Configuration::updateValue('SECUPAY_PAYMENT_VOID', '')
            || !Configuration::updateValue('SECUPAY_SENDEXPERIENCEAUTO', '')
            || !Configuration::updateValue('SECUPAY_PAYMENT_AUTHORIZED', '')
            || !Configuration::updateValue('SECUPAY_BLOCK_LOGO', '')
            || !Configuration::updateValue('SECUPAY_SENDTRASHIPPINGAUTO', '')
            || !$this->registerHook('payment')
            || !$this->registerHook('displayPayment')
            || !$this->registerHook('displayPaymentEU')
            || !$this->registerHook('displayTop')
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('leftColumn')
            || !$this->registerHook('ActionAdminOrdersTrackingNumberUpdate')
            || !$this->registerHook('displayPDFInvoice')) {
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

    /**
     * @return bool
     */
    private function createTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'secupay`(
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
        )';

        if (!Db::getInstance()
               ->Execute($sql)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function installOrderStates()
    {
        if (configuration::get('SECUPAY_WAIT_FOR_CONFIRM') < 1) {
            $orderState              = new OrderState();
            $orderState->name        = $this->l('Waiting for confirmation from secupay');
            $orderState->invoice     = false;
            $orderState->prepay      = true;
            $orderState->send_email  = false;
            $orderState->module_name = $this->name;
            $orderState->color       = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden      = false;
            $orderState->logable     = false;
            $orderState->delivery    = false;
            $orderState->shipped     = false;
            $orderState->paid        = false;
            $orderState->template    = 'cheque';

            $orderState->name = array(
                (int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('Waiting for confirmation from secupay')),
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_WAIT_FOR_CONFIRM', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif',
                        dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_CONFIRMED') < 1) {
            $orderState              = new OrderState();
            $orderState->name        = $this->l('secupay payment confirmed');
            $orderState->invoice     = true;
            $orderState->prepay      = true;
            $orderState->send_email  = true;
            $orderState->module_name = $this->name;
            $orderState->color       = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden      = false;
            $orderState->logable     = false;
            $orderState->delivery    = false;
            $orderState->shipped     = false;
            $orderState->paid        = false;
            $orderState->template    = 'payment';

            $orderState->name = array(
                (int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment confirmed')),
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_CONFIRMED', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif',
                        dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_DENIED') < 1) {
            $orderState              = new OrderState();
            $orderState->name        = $this->l('secupay payment denied');
            $orderState->invoice     = false;
            $orderState->send_email  = false;
            $orderState->module_name = $this->name;
            $orderState->color       = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden      = false;
            $orderState->logable     = false;
            $orderState->delivery    = false;
            $orderState->shipped     = false;
            $orderState->paid        = false;
            $orderState->template    = 'payment_error';

            $orderState->name = array(
                (int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment denied')),
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_DENIED', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif',
                        dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_ISSUE') < 1) {
            $orderState              = new OrderState();
            $orderState->name        = $this->l('secupay payment issue');
            $orderState->invoice     = false;
            $orderState->send_email  = false;
            $orderState->module_name = $this->name;
            $orderState->color       = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden      = false;
            $orderState->logable     = false;
            $orderState->delivery    = false;
            $orderState->shipped     = false;
            $orderState->paid        = false;
            $orderState->template    = 'payment_error';

            $orderState->name = array(
                (int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment issue')),
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_ISSUE', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif',
                        dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_VOID') < 1) {
            $orderState              = new OrderState();
            $orderState->name        = $this->l('secupay payment void');
            $orderState->invoice     = false;
            $orderState->send_email  = false;
            $orderState->module_name = $this->name;
            $orderState->color       = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden      = false;
            $orderState->logable     = false;
            $orderState->delivery    = false;
            $orderState->shipped     = false;
            $orderState->paid        = false;
            $orderState->template    = 'payment_error';

            $orderState->name = array(
                (int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment void')),
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_VOID', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif',
                        dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        if (configuration::get('SECUPAY_PAYMENT_AUTHORIZED') < 1) {
            $orderState              = new OrderState();
            $orderState->name        = $this->l('secupay payment authorized');
            $orderState->invoice     = false;
            $orderState->prepay      = false;
            $orderState->send_email  = false;
            $orderState->module_name = $this->name;
            $orderState->color       = '#28225C';
            $orderState->unremovable = true;
            $orderState->hidden      = false;
            $orderState->logable     = false;
            $orderState->delivery    = false;
            $orderState->shipped     = false;
            $orderState->paid        = false;
            $orderState->template    = 'payment';

            $orderState->name = array(
                (int) Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('secupay payment authorized')),
            );
            if ($orderState->add()) {
                Configuration::updateValue('SECUPAY_PAYMENT_AUTHORIZED', $orderState->id);
                if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                    // TODO: Add small secupay-Logo
                    copy(
                        dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif',
                        dirname(dirname(dirname(__FILE__))) . '/img/os/' . $orderState->id . '.gif'
                    );
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall module.
     *
     * @see PaymentModule::uninstall()
     */
    public function uninstall()
    {
        PrestaShopLogger::addLog(
            'Secupay:uninstall',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        if (!Configuration::deleteByName('SECUPAY_API', '')
            || !Configuration::deleteByName('SECUPAY_DEMO', '')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_CC', '')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_DEBIT', '')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_INVOICE', '')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_PREPAY', '')
            || !Configuration::deleteByName('SECUPAY_COUNTRY_SOFORT', '')
            || !Configuration::deleteByName('SECUPAY_PAY_CC', '')
            || !Configuration::deleteByName('SECUPAY_PAY_DEBIT', '')
            || !Configuration::deleteByName('SECUPAY_PAY_INVOICE', '')
            || !Configuration::deleteByName('SECUPAY_PAY_PREPAY', '')
            || !Configuration::deleteByName('SECUPAY_PAY_SOFORT', '')
            || !Configuration::deleteByName('SECUPAY_INVOICE_SECURE', '')
            || !Configuration::deleteByName('SECUPAY_DEBIT_SECURE', '')
            || !Configuration::deleteByName('SECUPAY_CC_SECURE', '')
            || !Configuration::deleteByName('SECUPAY_SOFORT_SECURE', '')
            || !Configuration::deleteByName('SECUPAY_DEFAULT_ORDERSTATE', '')
            || !Configuration::deleteByName('SECUPAY_SENDINVOICENUMBERAUTO', '')
            || !Configuration::deleteByName('SECUPAY_WAIT_FOR_CONFIRM', '')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_CONFIRMED', '')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_DENIED', '')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_ISSUE', '')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_VOID', '')
            || !Configuration::deleteByName('SECUPAY_SENDEXPERIENCEAUTO', '')
            || !Configuration::deleteByName('SECUPAY_PAYMENT_AUTHORIZED', '')
            || !Configuration::deleteByName('SECUPAY_BLOCK_LOGO', '')
            || !Configuration::deleteByName('SECUPAY_SENDTRASHIPPINGAUTO', '')
            || !Configuration::deleteByName('CONF_SECUPAY_FIXED', '')
            || !Configuration::deleteByName('CONF_SECUPAY_VAR', '')
            || !Configuration::deleteByName('CONF_SECUPAY_FIXED_FOREIGN', '')
            || !Configuration::deleteByName('CONF_SECUPAY_VAR_FOREIGN', '')
            || !$this->unregisterHook('payment')
            || !$this->unregisterHook('displayPayment')
            || !$this->unregisterHook('displayPaymentEU')
            || !$this->unregisterHook('displayTop')
            || !$this->unregisterHook('paymentOptions')
            || !$this->unregisterHook('leftColumn')
            || !$this->unregisterHook('ActionAdminOrdersTrackingNumberUpdate')
            || !$this->unregisterHook('displayPDFInvoice')
            || !parent::uninstall()) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        PrestaShopLogger::addLog('Secupay:getContent', 1, null, 'Secupay Plugin', null, true);
        $this->processConfiguration();
        $this->assignConfiguration();
        return $this->display(__FILE__, 'configuration.tpl');
    }

    /**
     *
     */
    public function processConfiguration()
    {
        if (Tools::isSubmit('secupay_pc_form')) {
            $apikey                = trim(Tools::getValue('apikey'));
            $demo                  = Tools::getValue('demo');
            $pay_cc                = Tools::getValue('pay_cc');
            $pay_debit             = Tools::getValue('pay_debit');
            $pay_invoice           = Tools::getValue('pay_invoice');
            $pay_prepay            = Tools::getValue('pay_prepay');
            $pay_sofort            = Tools::getValue('pay_sofort');
            $invoice_secure        = Tools::getValue('invoice_secure');
            $debit_secure          = Tools::getValue('debit_secure');
            $cc_secure             = Tools::getValue('cc_secure');
            $sofort_secure         = Tools::getValue('sofort_secure');
            $default_orderstate    = Tools::getValue('order_state');
            $sendinvoicenumberauto = Tools::getValue('sendinvoicenumberauto');
            $block_logo            = Tools::getValue('block_logo');
            $country_cc            = Tools::getValue('country_cc');
            $country_debit         = Tools::getValue('country_debit');
            $country_invoice       = Tools::getValue('country_invoice');
            $country_prepay        = Tools::getValue('country_prepay');
            PrestaShopLogger::addLog(
                'Secupay:processConfiguration:pay_invoice_enabled1' . print_r($invoice_secure, true),
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            if ('' === $apikey) {
                Configuration::updateValue('SECUPAY_API', '{%SPAPIKEY%}');
            } else {
                Configuration::updateValue('SECUPAY_API', $apikey);
            }
            Configuration::updateValue('SECUPAY_DEMO', $demo);
            Configuration::updateValue('SECUPAY_PAY_CC', $pay_cc);
            Configuration::updateValue('SECUPAY_PAY_DEBIT', $pay_debit);
            Configuration::updateValue('SECUPAY_PAY_INVOICE', $pay_invoice);
            Configuration::updateValue('SECUPAY_PAY_PREPAY', $pay_prepay);
            Configuration::updateValue('SECUPAY_PAY_SOFORT', $pay_sofort);
            Configuration::updateValue('SECUPAY_INVOICE_SECURE', $invoice_secure);
            Configuration::updateValue('SECUPAY_DEBIT_SECURE', $debit_secure);
            Configuration::updateValue('SECUPAY_CC_SECURE', $cc_secure);
            Configuration::updateValue('SECUPAY_SOFORT_SECURE', $sofort_secure);
            Configuration::updateValue('SECUPAY_DEFAULT_ORDERSTATE', $default_orderstate);
            Configuration::updateValue('SECUPAY_SENDINVOICENUMBERAUTO', $sendinvoicenumberauto);
            Configuration::updateValue('SECUPAY_BLOCK_LOGO', $block_logo);
            Configuration::updateValue('SECUPAY_COUNTRY_CC', $country_cc);
            Configuration::updateValue('SECUPAY_COUNTRY_DEBIT', $country_debit);
            Configuration::updateValue('SECUPAY_COUNTRY_INVOICE', $country_invoice);
            Configuration::updateValue('SECUPAY_COUNTRY_PREPAY', $country_prepay);

            $this->context->smarty->assign(
                array(
                    'confirmation' => 'ok',
                )
            );
        }
    }

    /**
     *
     */
    public function assignConfiguration()
    {
        $apikey                        = Configuration::get('SECUPAY_API');
        $country_cc                    = Configuration::get('SECUPAY_COUNTRY_CC');
        $country_debit                 = Configuration::get('SECUPAY_COUNTRY_DEBIT');
        $country_invoice               = Configuration::get('SECUPAY_COUNTRY_INVOICE');
        $country_prepay                = Configuration::get('SECUPAY_COUNTRY_PREPAY');
        $country_sofort                = Configuration::get('SECUPAY_COUNTRY_SOFORT');
        $cc_secure                     = Configuration::get('SECUPAY_CC_SECURE');
        $debit_secure                  = Configuration::get('SECUPAY_DEBIT_SECURE');
        $invoice_secure                = Configuration::get('SECUPAY_INVOICE_SECURE');
        $sofort_secure                 = Configuration::get('SECUPAY_SOFORT_SECURE');
        $demo                          = Configuration::get('SECUPAY_DEMO');
        $sendinvoicenumberauto         = Configuration::get('SECUPAY_SENDINVOICENUMBERAUTO');
        $block_logo                    = Configuration::get('SECUPAY_BLOCK_LOGO');
        $requestData                   = array();
        $pay_cc_enabled                = false;
        $pay_debit_enabled             = false;
        $pay_invoice_enabled           = false;
        $pay_prepay_enabled            = false;
        $pay_sofort_enabled            = false;
        $requestData['data']['apikey'] = $apikey;
        $sp_api                        = new secupay_api($requestData, 'gettypes', 'application/json', '');
        $res                           = $sp_api->request();

        if ($res->data) {
            in_array('invoice', $res->data) ? $pay_invoice_enabled = true : $pay_invoice_enabled = false;
            in_array('creditcard', $res->data) ? $pay_cc_enabled = true : $pay_cc_enabled = false;
            in_array('debit', $res->data) ? $pay_debit_enabled = true : $pay_debit_enabled = false;
            in_array('prepay', $res->data) ? $pay_prepay_enabled = true : $pay_prepay_enabled = false;
            in_array('sofort', $res->data) ? $pay_sofort_enabled = true : $pay_sofort_enabled = false;
        }

        if (true === $pay_cc_enabled) {
            $pay_cc = Configuration::get('SECUPAY_PAY_CC');
        } else {
            $pay_cc = false;
        }
        if (true === $pay_debit_enabled) {
            $pay_debit = Configuration::get('SECUPAY_PAY_DEBIT');
        } else {
            $pay_debit = false;
        }
        if (true === $pay_invoice_enabled) {
            $pay_invoice = Configuration::get('SECUPAY_PAY_INVOICE');
        } else {
            $pay_invoice = false;
        }
        if (true === $pay_prepay_enabled) {
            $pay_prepay = Configuration::get('SECUPAY_PAY_PREPAY');
        } else {
            $pay_prepay = false;
        }
        if (true === $pay_sofort_enabled) {
            $pay_sofort = Configuration::get('SECUPAY_PAY_SOFORT');
        } else {
            $pay_sofort = false;
        }

        false !== Configuration::get('SECUPAY_DEFAULT_ORDERSTATE') ? $default_orderstate = Configuration::get(
            'SECUPAY_DEFAULT_ORDERSTATE'
        ) : $default_orderstate = Configuration::get('PS_OS_PREPARATION');
        $this->context->smarty->assign(
            array(
                'apikey'                => trim($apikey),
                'demo'                  => $demo,
                'pay_cc'                => $pay_cc,
                'pay_cc_enabled'        => $pay_cc_enabled,
                'pay_debit'             => $pay_debit,
                'pay_debit_enabled'     => $pay_debit_enabled,
                'pay_invoice'           => $pay_invoice,
                'pay_invoice_enabled'   => $pay_invoice_enabled,
                'pay_prepay'            => $pay_prepay,
                'pay_prepay_enabled'    => $pay_prepay_enabled,
                'pay_sofort'            => $pay_sofort,
                'pay_sofort_enabled'    => $pay_sofort_enabled,
                'invoice_secure'        => $invoice_secure,
                'debit_secure'          => $debit_secure,
                'cc_secure'             => $cc_secure,
                'sofort_secure'         => $sofort_secure,
                'sendinvoicenumberauto' => $sendinvoicenumberauto,
                'block_logo'            => $block_logo,
                'orderstates'           => $this->getOrderStates($default_orderstate),
                'orderstate'            => $default_orderstate,
                'country_cc'            => trim($country_cc),
                'country_debit'         => trim($country_debit),
                'country_invoice'       => trim($country_invoice),
                'country_prepay'        => trim($country_prepay),
                'country_sofort'        => trim($country_sofort),
            )
        );
    }

    /**
     * @param null $selected
     *
     * @return mixed
     */
    private function getOrderStates($selected = null)
    {
        PrestaShopLogger::addLog('Secupay:getOrderStates', 1, null, 'Secupay Plugin', null, true);
        $order_states = OrderState::getOrderStates((int) $this->context->cookie->id_lang);

        $result = '';
        foreach ($order_states as $state) {
            $result .= '<option value="' . $state['id_order_state'] . '" ';
            $result .= ($state['id_order_state'] === $selected ? 'selected="selected"' : '');
            $result .= '>' . $state['name'] . '</option>';
        }
        return $order_states;
    }

    /**
     * @param $params
     *
     * @return bool
     */
    public function hookPaymentOptions($params)
    {
        PrestaShopLogger::addLog('Secupay:hookPaymentOptions', 1, null, 'Secupay Plugin', null, true);
        if (!$this->active) {
            return false;
        }
        $controller = $this->getHookController('displayPaymentOptions');
        return $controller->run($params);
    }

    /**
     * @param $hook_name
     *
     * @return mixed
     */
    private function getHookController($hook_name)
    {
        PrestaShopLogger::addLog('Secupay:getHookController', 1, null, 'Secupay Plugin', null, true);
        require_once dirname(__FILE__) . '/controllers/hook/' . $hook_name . '.php';
        $controller_name = $this->name . $hook_name . 'Controller';
        $controller      = new $controller_name($this, __FILE__, $this->_path);
        return $controller;
    }

    /**
     *
     */
    public function hookDisplayTop()
    {
        PrestaShopLogger::addLog('Secupay:hookDisplayTop', 1, null, 'Secupay Plugin', null, true);
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return;
        }
    }
    /**
     * @param $params
     *
     * @return array|bool
     */
    public function hookDisplayPayment($params)
    {
        PrestaShopLogger::addLog('Secupay:hookDisplayPayment', 1, null, 'Secupay Plugin', null, true);
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return array();
        }
        if (!$this->active) {
            return false;
        }
        $controller = $this->getHookController('displayPayment');
        return $controller->run($params);
    }
    /**
     * @param $params
     *
     * @return array
     */
    public function hookDisplayPaymentEU($params)
    {
        PrestaShopLogger::addLog('Secupay:hookDisplayPaymentEU', 1, null, 'Secupay Plugin', null, true);
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return array();
        }
        $controller = $this->getHookController('displayPaymentEU');
        return $controller->run($params);
    }

    /**
     * @param $params
     *
     * @return bool
     */
    public function hookDisplayPaymentReturn($params)
    {
        PrestaShopLogger::addLog('Secupay:hookDisplayPaymentReturn', 1, null, 'Secupay Plugin', null, true);
        if (!$this->active) {
            return false;
        }
        $controller = $this->getHookController('displayPaymentReturn');
        return $controller->run($params);
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function hookDisplayPDFInvoice($params)
    {
        PrestaShopLogger::addLog('Secupay:hookDisplayPDFInvoice', 1, null, 'Secupay Plugin', null, true);
        $controller = $this->getHookController('displayPDFInvoice');
        return $controller->run($params);
    }

    /**
     * @param $pt
     * @param $option
     *
     * @return mixed
     */
    public function getLangPayment($pt, $option)
    {
        PrestaShopLogger::addLog(
            'Secupay:getLangPayment:' . $pt . ':' . $option,
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $language = $this->l($pt, $option);
        return $language;
    }

    /**
     *
     */
    public function deleteOrderState()
    {
        $orderState = new OrderState((int) Configuration::get('SECUPAY_WAIT_FOR_CONFIRM'));
        $orderState->delete();
        $orderState = new OrderState((int) Configuration::get('SECUPAY_PAYMENT_CONFIRMED'));
        $orderState->delete();
        $orderState = new OrderState((int) Configuration::get('SECUPAY_PAYMENT_DENIED'));
        $orderState->delete();
        $orderState = new OrderState((int) Configuration::get('SECUPAY_PAYMENT_ISSUE'));
        $orderState->delete();
        $orderState = new OrderState((int) Configuration::get('SECUPAY_PAYMENT_VOID'));
        $orderState->delete();
        $orderState = new OrderState((int) Configuration::get('SECUPAY_PAYMENT_AUTHORIZED'));
        $orderState->delete();
        $orderState = new OrderState((int) Configuration::get('SECUPAY_PAY_DEBIT'));
        $orderState->delete();
    }

    /**
     * @param $cart
     *
     * @return bool
     */
    public function checkCurrency($cart)
    {
        PrestaShopLogger::addLog('Secupay:checkCurrency', 1, null, 'Secupay Plugin', null, true);
        $currency_order    = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id === $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     *
     */
    public function setMedia()
    {
        PrestaShopLogger::addLog('Secupay:setMedia', 1, null, 'Secupay Plugin', null, true);
        parent::setMedia();
        $this->path = __PS_BASE_URI__ . 'modules/secupay/';
        $this->context->controller->addCSS($this->path . 'views/css/secupay.css', 'all');
    }

    /**
     * @param $pt
     *
     * @return bool
     */
    public function isPaymentTypeOkay($pt)
    {
        PrestaShopLogger::addLog('Secupay:isPaymentTypeOkay', 1, null, 'Secupay Plugin', null, true);
        return 'invoice' === $pt || 'creditcard' === $pt || 'debit' === $pt || 'prepay' === $pt || 'sofort' === $pt;
    }

    /**
     * @param null $code
     *
     * @return mixed
     */
    public function isSupportedLang($code = null)
    {
        PrestaShopLogger::addLog('Secupay:isSupportedLang', 1, null, 'Secupay Plugin', null, true);
        if (null === $code) {
            $code = Language::getIsoById((int) $this->context->cart->id_lang);
        }

        if (isset($this->languages[$code])) {
            return $this->languages[$code];
        }

        return $this->languages['de'];
    }
}
