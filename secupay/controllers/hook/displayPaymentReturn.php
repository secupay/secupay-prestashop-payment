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

class SecupayDisplayPaymentReturnController
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
     * @return bool
     */
    public function run($params)
    {
        $state = $params['objOrder']->getCurrentState();

        $this->context->smarty->assign(array(
            'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
            'id_order' => $params['objOrder']->id,
            'cart' => $this->context->cart,
            'status' => $state
        ));

        if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
            $this->context->smarty->assign('reference', $params['objOrder']->reference);
        }

        if (Tools::getValue('success')) {
            if (Tools::getValue('success') == $params['objOrder']->reference) {
                return $this->module->display($this->file, 'confirmation.tpl');
            } else {
                return $this->makeIframe($params);
            }
        } elseif (Tools::getValue('cancel')) {
            if (Tools::getValue('cancel') == $params['objOrder']->reference) {
                if ($state == Configuration::get('SECUPAY_WAIT_FOR_CONFIRM')) {
                    $new_history = new OrderHistory();
                    $new_history->id_order = $params['objOrder']->id;
                    $new_history->changeIdOrderState(
                        Configuration::get('PS_OS_CANCELED'),
                        $params['objOrder']->id,
                        true
                    );
                    //TODO: Why?
                    $new_history->addWithemail(true);
                    var_dump($params['objOrder']->id_cart);
                    $this->refillCart($params['objOrder']->id);
                }
                return $this->module->display($this->file, 'cancel.tpl');
            } else {
                return $this->makeIframe($params);
            }
        } else {
            return $this->makeIframe($params);
        }
        return false;
    }

    private function makeIframe($params)
    {
        //prepare data
        $store_name = Configuration::get('PS_SHOP_NAME');
        $amount = $params['total_to_pay'];


        // DATA
        $data = array();

        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $data['demo'] = Configuration::get('SECUPAY_DEMO');
        $data['apikey'] = Configuration::get('SECUPAY_API');
        $data['shop'] = "Prestashop";
        $data['shopversion'] = Configuration::get('PS_INSTALL_VERSION');
        $data['modulversion'] = $this->module->version;
        $data['amount'] = $amount * 100;
        $data['currency'] = $params['currencyObj']->iso_code;
        $data['apiversion'] = API_VERSION;

        $data['hninstreet'] = 1;

        // selection Payment
        $pt = Tools::getValue('pt');
        if ($this->module->isPaymentTypeOkay($pt)) {
            $data["payment_type"] = $pt;
        }
        //$encode = new Secupay_Encryption();
        $data["payment_action"] = 'sale';
        $data["url_push"] = "http://" .
        $_SERVER['SERVER_NAME'] . __PS_BASE_URI__ . "modules/" .
        $params['objOrder']->module . "/push.php?secupay_mod=" .
        call_user_func_array(
            "base64_encode",
            array(
                $params['objOrder']->reference . "-" .
                $params['objOrder']->id . "-SP"
            )
        );
        // language selection (DE or anything != DE is EN)
        $lang_sql = "select * from " . _DB_PREFIX_ . "lang where id_lang = '" . $params['objOrder']->id_lang . "'";
        $lang_res = Db::getInstance()->executeS($lang_sql);
        if ($lang_res[0]['iso_code'] == "de") {
            $data['language'] = 'de_DE';
        } else {
            $data['language'] = 'en_US';
        }

        $data['url_success'] = "http://" . $_SERVER['SERVER_NAME'] .
        $_SERVER['REQUEST_URI'] . "&success=" . $params['objOrder']->reference;
        $data['url_failure'] = "http://" . $_SERVER['SERVER_NAME'] .
        $_SERVER['REQUEST_URI'] . "&cancel=" . $params['objOrder']->reference;
        $data['purpose'] = "Bestellung " . $params['objOrder']->reference . " vom " . date("d.m.y", time()) . "|bei " .
        $store_name . "|Bei Fragen TEL 035955755055";

        // customer
        $sql = "SELECT b.company, f.name AS salutation, b.lastname, b.firstname, b.address1, b.postcode, " .
            "b.city, b.phone, b.phone_mobile, c.iso_code, d.id_customer,d.email, d.birthday, e.iso_code AS currency " .
            "FROM " . _DB_PREFIX_ . "orders a " .
            "LEFT JOIN " . _DB_PREFIX_ . "address b ON b.id_address = a.id_address_invoice " .
            "LEFT JOIN " . _DB_PREFIX_ . "country c ON c.id_country = b.id_country " .
            "LEFT JOIN " . _DB_PREFIX_ . "customer d ON d.id_customer = a.id_customer " .
            "LEFT JOIN " . _DB_PREFIX_ . "currency e ON e.id_currency = a.id_currency " .
            "LEFT JOIN " . _DB_PREFIX_ . "gender_lang f ON f.id_gender = d.id_gender AND f.id_lang = '" .
            $params['objOrder']->id_lang . "'" . "WHERE a.id_order = '" . $params['objOrder']->id . "'";

        $res = Db::getInstance()->executeS($sql);
        if (count($res) == 1) {
            $data['title'] = $res[0]['salutation'];
            $data['company'] = $res[0]['company'];
            $data['lastname'] = $res[0]['lastname'];
            $data['firstname'] = $res[0]['firstname'];
            $data['street'] = $res[0]['address1'];
            $data['zip'] = $res[0]['postcode'];
            $data['city'] = $res[0]['city'];
            $data['telephone'] = $res[0]['phone'];
            $data['country'] = $res[0]['iso_code'];
            $data['email'] = $res[0]['email'];
            $data['dob'] = $res[0]['birthday'];
            $data['order_id'] = $params['objOrder']->id;
            $data['order_reference'] = $params['objOrder']->reference;
            //experience
            $experiencep = 0;
            $experiencen = 0;

            if (Configuration::get('SECUPAY_SENDEXPERIENCEAUTO')) {
                $sqln = "SELECT count(id_customer) FROM " . _DB_PREFIX_ . "orders WHERE id_customer = '" .
                $res[0]['id_customer'] . "' and current_state in (6,7,8)";
                $sqlp = "SELECT count(id_customer) FROM " . _DB_PREFIX_ . "orders WHERE id_customer = '" .
                $res[0]['id_customer'] . "' and current_state in (2,3,4,5)";
                $statusn = Db::getInstance()->getRow($sqln);
                $statusp = Db::getInstance()->getRow($sqlp);
                if ($statusn <= 0) {
                    $statusn = 0;
                } else {
                    $experiencen = $statusn['count(id_customer)'];
                }
                if ($statusp <= 0) {
                    $statusp = 0;
                } else {
                    $experiencep = $statusp['count(id_customer)'];
                }
            }
            $data['experience']['negative'] = $experiencen;
            $data['experience']['positive'] = $experiencep;
        }
        // delivery address
        $sql = "SELECT b.company, b.lastname, b.firstname, b.address1, b.postcode, b.city, c.iso_code " .
            "FROM " . _DB_PREFIX_ . "orders a, " . _DB_PREFIX_ . "address b, " . _DB_PREFIX_ . "country c " .
            "WHERE a.id_order = '" . $params['objOrder']->id . "' " .
            "AND a.id_address_delivery = b.id_address " .
            "AND b.id_country = c.id_country";
        $res = Db::getInstance()->executeS($sql);
        if (count($res) == 1) {
            $data['delivery_address']['firstname'] = $res[0]['firstname'];
            $data['delivery_address']['lastname'] = $res[0]['lastname'];
            $data['delivery_address']['company'] = $res[0]['company'];
            $data['delivery_address']['street'] = $res[0]['address1'];
            $data['delivery_address']['zip'] = $res[0]['postcode'];
            $data['delivery_address']['city'] = $res[0]['city'];
            $data['delivery_address']['country'] = $res[0]['iso_code'];
        }

        // Cart -> Customize
        $sql = "SELECT a.product_reference, a.product_ean13, a.product_name, a.unit_price_tax_incl, " .
            "a.total_price_tax_incl, a.product_quantity, d.rate " .
            "FROM " . _DB_PREFIX_ . "order_detail a, " . _DB_PREFIX_ . "product b,
            " . _DB_PREFIX_ . "tax_rule c, " . _DB_PREFIX_ . "tax d " .
            "WHERE a.id_order = '" . $params['objOrder']->id . "' " .
            "AND a.product_id = b.id_product " .
            "AND b.id_tax_rules_group = b.id_tax_rules_group " .
            "AND c.id_tax = d.id_tax " .
            "GROUP BY a.product_name";


        $res = Db::getInstance()->executeS($sql);
        for ($i = 0; $i < count($res); $i++) {
            $eprice = 100 * $res[$i]['unit_price_tax_incl'];
            $gprice = 100 * $res[$i]['total_price_tax_incl'];

            $data['basket'][$i]['article_number'] = $res[$i]['product_reference'];
            $data['basket'][$i]['name'] = $res[$i]['product_name'];
            // $data['basket'][$i]['model'] = '';
            $data['basket'][$i]['ean'] = $res[$i]['product_ean13'];
            $data['basket'][$i]['quantity'] = $res[$i]['product_quantity'];
            $data['basket'][$i]['price'] = $eprice;
            $data['basket'][$i]['total'] = $gprice;
            $data['basket'][$i]['tax'] = $res[$i]['rate'];
        }

        $requestData = array();
        $requestData['data'] = $data;

        $api = new secupay_api($requestData, 'init', 'application/json', false, $data['language']);

        $api_res = $api->request();

        if (empty($api_res)) {
            $new_history = new OrderHistory();
            $new_history->id_order = $params['objOrder']->id;
            $new_history->changeIdOrderState(Configuration::get('PS_OS_CANCELED'), $params['objOrder']->id, true);
            //TODO: Why?
            $new_history->addWithemail(true);
            var_dump($params['objOrder']->id_cart);
            $this->refillCart($params['objOrder']->id);

            return $this->module->display($this->file, 'cancel.tpl');
        } elseif ($api_res->status != 'ok') {
            $error = utf8_decode($api_res->errors[0]->message);
            $this->context->smarty->assign(array(
                'msg' => utf8_encode(urldecode($error)),
                'status' => $api_res->status
            ));

            return $this->module->display($this->file, 'error.tpl');
        } else {
            $this->saveOrderHash(
                $params['objOrder']->id,
                $api_res->data->hash,
                $pt,
                $requestData['data'],
                $api_res,
                $params['objOrder']->reference,
                $data['amount'],
                'created'
            );

            $this->context->controller->addCSS($this->_path . 'views/css/secupay.css', 'all');
            $this->context->controller->addJS($this->_path . 'views/js/secupay.js');

            $this->context->smarty->assign('iframesrc', stripcslashes($api_res->data->iframe_url));
            return $this->module->display($this->file, 'iframe.tpl');
        }
    }

    private function refillCart($id_order)
    {
        $oldCart = new Cart(Order::getCartIdStatic($id_order, $this->context->customer->id));
        $duplication = $oldCart->duplicate();
        if (!$duplication || !Validate::isLoadedObject($duplication['cart'])) {
            $this->errors[] = Tools::displayError('Sorry. We cannot renew your order.');
        } elseif (!$duplication['success']) {
            $this->errors[] = Tools::displayError(
                'Some items are no longer available, and we are unable to renew your order.'
            );
        } else {
            $this->context->cookie->id_cart = $duplication['cart']->id;
            $this->context->cookie->write();
            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                Tools::redirect('index.php?controller=order-opc');
            }
            Tools::redirect('index.php?controller=order');
        }
    }

    private function saveOrderHash($order_id, $hash, $pt, $req, $res, $uid, $amount, $status)
    {
        Db::getInstance()->insert('secupay', array(
            'id_order' => (int)$order_id,
            'searchcode' => (int)$order_id,
            'hash' => $hash,
            'apikey' => Configuration::get('SECUPAY_API'),
            'payment_type' => $pt,
            'req_data' => addslashes(Tools::jsonEncode($req)),
            'ret_data' => addslashes(Tools::jsonEncode($res)),
            'updated' => date("Y-m-d H:i:s"),
            'created' => date("Y-m-d H:i:s"),
            'unique_id' => $uid,
            'amount' => $amount,
            'status' => $status

        ));
    }
}
