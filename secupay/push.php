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

include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/secupay.php');

$response='';
$hash = Tools::getValue('hash');
$status_id = Tools::getValue('status_id');
$status_description = Tools::getValue('status_description');
$changed = Tools::getValue('changed');
$payment_status = Tools::getValue('payment_status');
$apikey = Tools::getValue('apikey');
$hint = Tools::getValue('hint');
$status = 0;
$_html = "";

$my_apikey = Configuration::get('SECUPAY_API');

if (ip2long($_SERVER['REMOTE_ADDR']) < ip2long('91.195.151.255')
    && ip2long('91.195.150.0') < ip2long($_SERVER['REMOTE_ADDR'])) {
    if ($my_apikey = $apikey) {
        $secupay = new Secupay();
        $new_history = new OrderHistory();
        $key = call_user_func_array(
            "base64_decode",
            array(Tools::getValue('secupay_mod'))
        );
        
        $result = explode("-", $key);
        if (count($result) == 3 && $result[2] == "SP") {
            $order_id = (int)$result[1];
            if ($order_id != 0) {
                $new_history->id_order = $order_id;

                $order_status_waiting = Configuration::get('SECUPAY_WAIT_FOR_CONFIRM');
                $order_status_accepted = Configuration::get('SECUPAY_PAYMENT_CONFIRMED');
                $order_status_denied = Configuration::get('SECUPAY_PAYMENT_DENIED');
                $order_status_issue = Configuration::get('SECUPAY_PAYMENT_ISSUE');
                $order_status_void = Configuration::get('SECUPAY_PAYMENT_VOID');
                $order_status_authorized = Configuration::get('SECUPAY_PAYMENT_AUTHORIZED');
                $order_status_default = Configuration::get('SECUPAY_DEFAULT_ORDERSTATE');

                // Status ermitteln
                $sql = "SELECT * FROM " . _DB_PREFIX_ . "order_history WHERE id_order = '" .
                $order_id . "' ORDER BY date_add DESC LIMIT 0,1";
                $res = Db::getInstance()->executeS($sql);
                if (count($res) == 1) {
                    $order_status = $res[0]['id_order_state'];
                }
                $sql = "SELECT * FROM " . _DB_PREFIX_ . "secupay WHERE hash = '" . $hash . "'";
                $res = Db::getInstance()->executeS($sql);
                if (count($res) == 1 and $res[0]['status'] !='accepted'and $res[0]['status'] !='denied') {
                    $payment_type = $res[0]['payment_type'];
                } else {
                    $payment_status = '';
                }

                switch ($payment_status) {
                    case 'accepted':
                        $order_status = $order_status_accepted;
                        break;
                    case 'denied':
                        $order_status = $order_status_denied;
                        break;
                    case 'issue':
                        $order_status = $order_status_issue;
                        break;
                    case 'void':
                        $order_status = $order_status_void;
                        break;
                    case 'authorized':
                        $order_status = $order_status_authorized;
                        if ($payment_type == 'prepay') {
                            $order_status = -99;
                        }
                        break;
                    default:
                        $response = 'ack=Disapproved&error=payment_status+not+supported';
                        $order_status = -99;
                        break;
                }
                if ($order_status !== -99) {
                    try {
                        $data = array();
                        $data['apikey'] = $my_apikey;
                        $data['hash'] = $hash;
                        $requestData = array();
                        $requestData['data'] = $data;
                        $api = new secupay_api($requestData, 'status', 'application/json', false, false);
                        $api_res = $api->request();
                        $trans_id=$api_res->data->trans_id;
                        $status=$api_res->data->status;
                        if (!empty($trans_id)) {
                            $sql = "update " . _DB_PREFIX_ . "secupay set trans_id='" . $trans_id .
                                 "',status='".$payment_status."' WHERE hash = '" . $hash . "'";
                            Db::getInstance()->executeS($sql);
                        }
                        if ($order_status == $order_status_accepted) {
                            $new_history->changeIdOrderState($order_status_default, $order_id, true);
                            $new_history->add(true);
                            if (!empty($trans_id)) {
                                $order = new Order($order_id);
                                $payments = $order->getOrderPaymentCollection();
                                $payments[0]->transaction_id = $trans_id;
                                $payments[0]->update();
                            }
                        } else {
                            $new_history->changeIdOrderState($order_status, $order_id, true);
                            $new_history->add(true);
                        }

                        $response = 'ack=Approved';
                    } catch (Exception $e) {
                        $response = 'ack=Disapproved&error=order+status+not+changed';
                    }
                }
            }
        }
    }
} else {
    $response = 'ack=Disapproved&error=request+invalid';
}
echo $response . '&' . http_build_query($_POST);
