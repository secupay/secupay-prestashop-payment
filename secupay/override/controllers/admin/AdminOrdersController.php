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

class AdminOrdersController extends AdminOrdersControllerCore
{
    public function postProcess()
    {
        if (Configuration::get('SECUPAY_API')) {
            parent::postProcess();
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                return '';
            }

            $sql = "SELECT v_send, v_status,payment_type FROM " . _DB_PREFIX_ .
            "secupay WHERE id_order = '" . Tools::getValue('id_order') . "'";
            $status = Db::getInstance()->getRow($sql);
            if ($status['v_send'] != 1) {
                if ($status['v_status'] != 1) {
                    if ($order->payment == 'secupay AG' && $order->shipping_number) {
                        $sql = "SELECT name FROM " . _DB_PREFIX_ . "carrier WHERE id_carrier = '" .
                        $order->id_carrier . "'";
                        $carrier = Db::getInstance()->getRow($sql);
                        $carrier = self::getCarrier($order->shipping_number, $carrier['name']);
                        $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET v_status='1', track_number='" .
                        $order->shipping_number . "', carrier_code='" . $carrier .
                        "' WHERE id_order = '" . Tools::getValue('id_order') . "'";
                        Db::getInstance()->execute($sql);
                    }
                    if (Configuration::get('SECUPAY_SENDINVOICENUMBERAUTO')) {
                        if ($order->payment == 'secupay AG' && $order->invoice_number) {
                            $sql = "SELECT name FROM " . _DB_PREFIX_ . "carrier WHERE id_carrier = '" .
                            $order->id_carrier . "'";
                            $carrier = Db::getInstance()->getRow($sql);
                            $carrier = self::getCarrier($order->shipping_number, $carrier['name']);
                            $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET searchcode='" .
                            $order->invoice_number . "', carrier_code='" .
                            $carrier . "' WHERE id_order = '" . Tools::getValue('id_order') . "'";
                            Db::getInstance()->execute($sql);
                        }
                    }
                    if (Configuration::get('SECUPAY_SENDSHIPPINGAUTO') && $order->invoice_number
                        && $order->shipping_number && $status['payment_type'] == 'invoice') {
                        $sql = "SELECT hash,apikey,track_number,carrier_code,searchcode,payment_type FROM " .
                        _DB_PREFIX_ . "secupay WHERE id_order = '" . Tools::getValue('id_order') . "'";
                        $vstatus = Db::getInstance()->getRow($sql);
                        $data = array();
                        $data['apikey'] = $vstatus['apikey'];
                        $data['hash'] = $vstatus['hash'];
                        $data['tracking']['provider'] = $vstatus['carrier_code'];
                        $data['tracking']['number'] = $vstatus['track_number'];
                        $data['invoice_number'] = $vstatus['searchcode'];
                        $requestData = array();
                        $requestData['data'] = $data;
                        $capture = self::getPayment($vstatus['payment_type']);
                        $api = new secupay_api($requestData, $capture, 'application/json', false, $data['language']);
                        $api_res = $api->request();
                        if ($api_res->status == 'ok'
                        or utf8_decode($api_res->errors[0]->code) == '0011') {
                            $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET v_send='1'
                            WHERE id_order = '" . Tools::getValue('id_order') . "'";
                            Db::getInstance()->execute($sql);
                        }
                    }
                }
                if ($status['v_status'] == 1 && Configuration::get('SECUPAY_SENDSHIPPINGAUTO')
                    or $status['v_status'] == 1 && Configuration::get('SECUPAY_SENDTRASHIPPINGAUTO')) {
                    $sql = "SELECT hash,apikey,track_number,carrier_code,searchcode,payment_type FROM " . _DB_PREFIX_ .
                    "secupay WHERE id_order = '" . Tools::getValue('id_order') . "'";
                    $vstatus = Db::getInstance()->getRow($sql);
                    $data = array();
                    $data['apikey'] = $vstatus['apikey'];
                    $data['hash'] = $vstatus['hash'];
                    $data['tracking']['provider'] = $vstatus['carrier_code'];
                    $data['tracking']['number'] = $vstatus['track_number'];
                    $data['invoice_number'] = $vstatus['searchcode'];
                    $requestData = array();
                    $requestData['data'] = $data;
                    $capture = self::getPayment($vstatus['payment_type']);
                    $api = new secupay_api($requestData, $capture, 'application/json', false, 'de_DE');
                    $api_res = $api->request();
                    if ($api_res->status == 'ok' or utf8_decode($api_res->errors[0]->message) ==
                        'Zahlung konnte nicht abgeschlossen werden') {
                        $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET v_send='1'
                        WHERE id_order = '" . Tools::getValue('id_order') . "'";
                        Db::getInstance()->execute($sql);
                    }
                }
            }
        }
    }

    public function __construct()
    {
        parent::__construct();

        //require functions library
        require_once('../modules/secupay/lib/secupay_api.php');

        if (Configuration::get('SECUPAY_API')) {
            $this->_join .= 'LEFT JOIN ' . _DB_PREFIX_ . 'secupay ct ON ct.id_order=a.id_order';
            $this->_select .= ', ct.payment_type as `payment_type`';
            $this->_select .= ', ct.v_send as `v_send`';
            $this->_select .= ', ct.v_status as `v_status`';
            $this->_select .= ', ct.searchcode as `searchcode`';
            $this->_select .= ', ct.id_secupay as `id_secupay`';
            $this->_select .= ', ct.hash as `hash`';
            $this->_select .= ', ct.apikey as `apikey`';
            $this->_select .= ', ct.carrier_code as `carrier_code`';
            $this->fields_list['payment_type'] = array(
                'title' => $this->l('Send Shipped to Secupay'),
                'align' => 'center',
                'orderby' => false,
                'search' => false,
                'callback' => 'sendShipIcons',
                'remove_onclick' => true
            );
        }
    }

    public function sendShipIcons($payment_type, $tr)
    {
        $order = new Order($tr['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }
        $sql = "SELECT name FROM " . _DB_PREFIX_ . "carrier WHERE id_carrier = '" . $order->id_carrier . "'";
        $carrier = Db::getInstance()->getRow($sql);
        $carrier = self::getCarrier($order->shipping_number, $carrier['name']);
        if ($order->delivery_number and $order->shipping_number and !$tr['carrier_code']) {
            $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET track_number='" . $order->shipping_number . "',
            carrier_code='" . $carrier . "' WHERE hash = '" . $tr['hash'] . "'";
            Db::getInstance()->execute($sql);
        }
        $this->context->smarty->assign(array(
            'hash' => $tr['hash'],
            'key' => $tr['apikey'],
            'carrier_code' => $tr['carrier_code'],
            'shipping_number' => $order->shipping_number,
            'invoice_number' => $order->invoice_number,
            'delivery_number' => $order->delivery_number
        ));
        if ($payment_type != 'invoice') {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_na.tpl');
        }
        if (!empty($tr['v_send'])) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_ok.tpl');
        }
        if ($payment_type == 'invoice' && empty($tr['v_send'])) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_icon.tpl');
        }
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_na.tpl');
    }

    public function getCarrier($trackingnumber, $provider)
    {
        if (
            preg_match(
                "/^1Z\s?[0-9A-Z]{3}\s?[0-9A-Z]{3}\s?[0-9A-Z]{2}\s?[0-9A-Z]{4}\s?[0-9A-Z]{3}\s?[0-9A-Z]$/i",
                $trackingnumber
            )
        ) {
            $resprovider = "UPS";
        } elseif (
        preg_match("/^\d{14}$/", $trackingnumber)
        ) {
            $resprovider = "HLG";
        } elseif (
        preg_match("/^\d{11}$/", $trackingnumber)
        ) {
            $resprovider = "GLS";
        } elseif (
            preg_match("/[A-Z]{3}\d{2}\.?\d{2}\.?(\d{3}\s?){3}/", $trackingnumber) ||
            preg_match("/[A-Z]{3}\d{2}\.?\d{2}\.?\d{3}/", $trackingnumber) ||
            preg_match("/(\d{12}|\d{16}|\d{20})/", $trackingnumber)
        ) {
            $resprovider = "DHL";
        } elseif (
            preg_match("/RR\s?\d{4}\s?\d{5}\s?\d(?=DE)/", $trackingnumber) ||
            preg_match("/NN\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE(\s)?\d{3})/", $trackingnumber) ||
            preg_match("/RA\d{9}(?=DE)/", $trackingnumber) || preg_match("/LX\d{9}(?=DE)/", $trackingnumber) ||
            preg_match("/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/", $trackingnumber) ||
            preg_match("/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/", $trackingnumber) ||
            preg_match("/XX\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/", $trackingnumber) ||
            preg_match("/RG\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/", $trackingnumber)
        ) {
            $resprovider = "DPAG";
        } else {
            $resprovider = $provider;
        }
        return $resprovider;
    }

    public function getPayment($payment)
    {
        if ($payment == 'invoice') {
            return 'capture';
        } else {
            return 'adddata';
        }
    }
}
