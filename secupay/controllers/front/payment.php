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

class SecupayPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool
     */
    public $ssl = true;
    /**
     * Show payment overview.
     *
     * @throws \Exception
     */
    public function initContent()
    {
        $this->display_column_left  = false;
        $this->display_column_right = false;
        parent::initContent();
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:initContent',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $cart         = $this->context->cart;
        $customer     = new Customer((int) $cart->id_customer);
        $currency     = $this->context->currency;
        $language     = $this->context->language;
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        if (!Validate::isLoadedObject($customer)
            || !Validate::isLoadedObject($currency)
            || !Validate::isLoadedObject($language)) {
            $errormsg = sprintf(
                '%s Error: (Invalid customer, language or currency object)',
                $this->module->displayName
            );
            PrestaShopLogger::addLog(
                'SecupayPaymentModuleFrontController:initContent' . $errormsg,
                3,
                null,
                'Secupay Plugin',
                (int) $this->context->cart->id,
                true
            );
            throw new \Exception($errormsg);
        }
        $data = $this->getTransactionData();
        $this->context->smarty->assign(
            'iframesrc',
            stripcslashes($data->data->iframe_url)
        );
        $domaunSSL = Tools::getShopDomainSsl(
            true,
            true
        );
        $this->context->smarty->assign(
            array(
                'this_path'     => $this->module->getPathUri(),
                'this_path_bw'  => $this->module->getPathUri(),
                'this_path_ssl' => $domaunSSL . __PS_BASE_URI__ . 'modules/' . $this->module->name . '/',
                'nb_products'   => $this->context->cart->nbProducts(),
                'cart_currency' => $this->context->cart->id_currency,
                'currencies'    => $this->module->getCurrency((int) $this->context->cart->id_currency),
                'total_amount'  => $this->context->cart->getOrderTotal(
                    true,
                    Cart::BOTH
                ),
                'path'          => $this->module->getPathUri(),
                'pt'            => Tools::getValue('pt'),
            )
        );
        if (version_compare(
            _PS_VERSION_,
            '1.7',
            '>='
        )) {
            PrestaShopLogger::addLog(
                'SecupayPaymentModuleFrontController:version_compare:1.7',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $this->setTemplate('module:secupay/views/templates/front/payment_17.tpl');
        } else {
            PrestaShopLogger::addLog(
                'SecupayPaymentModuleFrontController:version_compare:1.6',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $this->setTemplate('payment.tpl');
        }
    }

    /**
     * @return object
     */
    protected function getTransactionData()
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getTransactionData',
            1,
            null,
            'Secupay Plugin',
            (int) $this->context->cart->id,
            true
        );
        $amount                 = $this->context->cart->getOrderTotal();
        $pt                     = Tools::getValue('pt');
        $data                   = array();
        $data['apikey']         = Configuration::get('SECUPAY_API');
        $data['apiversion']     = API_VERSION;
        $data['shop']           = 'Prestashop';
        $data['shopversion']    = Configuration::get('PS_INSTALL_VERSION');
        $data['modulversion']   = $this->module->version;
        $data['ip']             = $_SERVER['REMOTE_ADDR'];
        $data['payment_action'] = 'sale';
        if ($this->module->isPaymentTypeOkay($pt)) {
            $data['payment_type'] = $pt;
        }
        $data['demo']     = ('sofort' === $pt) ? 0 : Configuration::get('SECUPAY_DEMO');
        $data['amount']   = $amount * 100;
        $data['currency'] = $this->context->currency->iso_code;
        if ('de' === $this->context->language->iso_code) {
            $data['language'] = 'de_DE';
        } else {
            $data['language'] = 'en_US';
        }
        //$data['hninstreet'] = 1;
        $data['order_reference'] = $this->context->cookie->checksum;
        $data['url_success']     = $this->context->shop->getBaseURL(true) . 'module/' . $this->module->name
            . '/validate?secupay_mod=' . call_user_func_array(
                'base64_encode',
                array(
                    $data['order_reference'] . '-' . $this->context->cart->id . '-SP' . '-success-' . $pt,
                )
            ) . '&success=' . $data['order_reference'];

        $data['url_failure'] = $this->context->shop->getBaseURL(true) . 'module/' . $this->module->name
            . '/validate?secupay_mod=' . call_user_func_array(
                'base64_encode',
                array(
                    $data['order_reference'] . '-' . $this->context->cart->id . '-SP' . '-cancel-' . $pt,
                )
            ) . '&cancel=' . $data['order_reference'];

        $data['url_push'] = $this->context->shop->getBaseURL(true) . 'modules/' . $this->module->name
            . '/push.php?secupay_mod=' . call_user_func_array(
                'base64_encode',
                array(
                    $data['order_reference'] . '-' . $this->context->cart->id . '-SP' . '-push-' . $pt,
                )
            );

        $data['purpose'] = $this->module->l('Order of') . date('d.m.y', time()) . $this->module->l('at')
            . Configuration::get('PS_SHOP_NAME') . $this->module->l('| For questions phone 035955755055');
        $data['cart_id'] = $this->context->cart->id;

        $sqln    = 'SELECT count(id_customer) as cCustomer FROM ' . _DB_PREFIX_ . "orders WHERE id_customer = '"
            . $this->context->customer->id . "' and current_state in (6,7,8)";
        $sqlp    = 'SELECT count(id_customer) as cCustomer FROM ' . _DB_PREFIX_ . "orders WHERE id_customer = '"
            . $this->context->customer->id . "' and current_state in (2,3,4,5)";
        $statusn = Db::getInstance()
                     ->getRow($sqln);
        $statusp = Db::getInstance()
                     ->getRow($sqlp);
        if ($statusn <= 0) {
            $experiencen = 0;
        } else {
            $experiencen = $statusn['cCustomer'];
        }
        if ($statusp <= 0) {
            $experiencep = 0;
        } else {
            $experiencep = $statusp['cCustomer'];
        }
        $data['experience']['negative'] = $experiencen;
        $data['experience']['positive'] = $experiencep;
        $data                           = array_merge(
            $data,
            $this->getPluginCustomer($this->context->customer)
        );
        $data                           = array_merge(
            $data,
            $this->getPluginAddress($this->context->cart->id_address_invoice)
        );
        $data['delivery_address']       = $this->getPluginDelivery($this->context->cart->id_address_delivery);
        $data['basket']                 = $this->getPluginBasket(Context::getContext()->cart->getProducts());
        $data['module_config']          = $this->getPluginConfig();
        $requestData                    = array();
        $requestData['data']            = $data;
        $api                            = new secupay_api(
            $requestData,
            'init',
            'application/json',
            false,
            $data['language']
        );
        $api_res                        = $api->request();
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:makeIframe:API_RES',
            1,
            null,
            'Secupay Plugin',
            (int) $this->context->cart->id,
            true
        );
        if (!empty($api_res)) {
            PrestaShopLogger::addLog(
                'SecupayPaymentModuleFrontController:makeIframe:no_API_RES',
                1,
                null,
                'Secupay Plugin',
                (int) $this->context->cart->id,
                true
            );
            $this->saveOrderHash(
                (int) $this->context->cart->id,
                $api_res->data->hash,
                $pt,
                $requestData['data'],
                $api_res,
                null,
                $data['amount'],
                'created'
            );
        }

        return $api_res;
    }

    /**
     * @param $customer
     *
     * @return array
     */
    private function getPluginCustomer($customer)
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginCustomer',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $PluginCustomer = array(
            'title'     => $customer->firstname,
            'company'   => $customer->company,
            'lastname'  => $customer->lastname,
            'firstname' => $customer->firstname,
            'email'     => $customer->email,
            'dob'       => $customer->birthday,
        );
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginCustomer' . print_r($PluginCustomer, true),
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );

        return $PluginCustomer;
    }

    /**
     * @param $adress_invoice_id
     *
     * @return array
     */
    private function getPluginAddress($adress_invoice_id)
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginAdress',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $billing_address = new Address($adress_invoice_id);
        $address         = array(
            'street'    => trim("{$billing_address->address1} {$billing_address->address2}"),
            'zip'       => $billing_address->postcode,
            'city'      => $billing_address->city,
            'telephone' => (!empty($billing_address->phone) ? $billing_address->phone : $billing_address->phone_mobile),
            'country'   => $this->context->country->iso_code,
        );
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginAddress' . print_r(
                $address,
                true
            ),
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );

        return $address;
    }

    /**
     * @param $adress_delivery_id
     *
     * @return array
     */
    private function getPluginDelivery($adress_delivery_id)
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginDelivery',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $delivery_address = new Address($adress_delivery_id);

        $address = array(
            'firstname' => $delivery_address->firstname,
            'lastname'  => $delivery_address->lastname,
            'company'   => $delivery_address->company,
            'street'    => trim("{$delivery_address->address1} {$delivery_address->address2}"),
            'zip'       => $delivery_address->postcode,
            'city'      => $delivery_address->city,
            'country'   => $delivery_address->country,
            'telephone' => (!empty($delivery_address->phone) ? $delivery_address->phone
                : $delivery_address->phone_mobile),
        );

        return $address;
    }

    /**
     * @param $products
     *
     * @return array
     */
    private function getPluginBasket($products)
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginBasket',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $basket = array();
        foreach ($products as $product) {
            $eprice   = 100 * (int) $product['price_wt'];
            $gprice   = 100 * (int) $product['total_wt'];
            $basket[] = array(
                'article_number' => $product['reference'],
                'name'           => $product['name'],
                'description'    => $product['description_short'],
                'ean'            => $product['ean13'],
                'quantity'       => (int) $product['cart_quantity'],
                'price'          => (int) $eprice,
                'total'          => (int) $gprice,
                'tax'            => (int) $product['rate'],
            );
        }

        return $basket;
    }

    /**
     * @return mixed
     */
    private function getPluginConfig()
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginConfig',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $module_config = array(
            'demo'                  => Configuration::get('demo'),
            'pay_cc'                => Configuration::get('pay_cc'),
            'pay_debit'             => Configuration::get('pay_debit'),
            'pay_invoice'           => Configuration::get('pay_invoice'),
            'pay_prepay'            => Configuration::get('pay_prepay'),
            'pay_sofort'            => Configuration::get('pay_sofort'),
            'invoice_secure'        => Configuration::get('invoice_secure'),
            'debit_secure'          => Configuration::get('debit_secure'),
            'sofort_secure'         => Configuration::get('sofort_secure'),
            'order_state'           => Configuration::get('order_state'),
            'sendinvoicenumberauto' => Configuration::get('sendinvoicenumberauto'),
            'block_logo'            => Configuration::get('block_logo'),
            'country_cc'            => Configuration::get('country_cc'),
            'country_debit'         => Configuration::get('country_debit'),
            'country_invoice'       => Configuration::get('country_invoice'),
            'country_prepay'        => Configuration::get('country_prepay'),
        );

        return $module_config;
    }

    /**
     * @param $order_id
     * @param $hash
     * @param $pt
     * @param $req
     * @param $res
     * @param $uid
     * @param $amount
     * @param $status
     */
    private function saveOrderHash($order_id, $hash, $pt, $req, $res, $uid, $amount, $status)
    {
        PrestaShopLogger::addLog(
            'SecupayPaymentModuleFrontController:getPluginConfig',
            1,
            null,
            'Secupay Plugin',
            $order_id,
            true
        );
        Db::getInstance()
          ->insert(
              'secupay',
              array(
                  'id_order'     => (int) $order_id,
                  'searchcode'   => (int) $order_id,
                  'hash'         => $hash,
                  'apikey'       => Configuration::get('SECUPAY_API'),
                  'payment_type' => $pt,
                  'req_data'     => addslashes(Tools::jsonEncode($req)),
                  'ret_data'     => addslashes(Tools::jsonEncode($res)),
                  'updated'      => date('Y-m-d H:i:s'),
                  'created'      => date('Y-m-d H:i:s'),
                  'unique_id'    => $uid,
                  'amount'       => $amount,
                  'status'       => $status,
              )
          );
    }
}
