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

class SecupayDisplayPDFInvoiceController
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
        $lang_sql = "select * from " . _DB_PREFIX_ . "lang where id_lang = '" .
        $this->context->cookie->__get('id_lang') . "'";
        $lang_res = Db::getInstance()->executeS($lang_sql);

        $lang_res[0]['iso_code'] == "de" ? $lng = 'de_DE' : $lng = 'en_US';

        $orderInfo = $this->getOrderInfo(Tools::getValue('id_order'));

        if (Tools::strlen($orderInfo['hash']) > 1) {
            $data = array(
                'apikey' => $orderInfo['apikey'],
                'hash' => $orderInfo['hash']
            );

            $requestData = array(
                'data' => $data
            );

            $api = new secupay_api($requestData, 'status', 'application/json', false, $lng);

            $api_res = $api->request();

            if ($api_res->status === 'ok') {
                if ($orderInfo['payment_type'] === 'invoice') {
                    $info = $api_res->data->opt;
                    $trans_id = Tools::substr(strrchr($info->transfer_payment_data->purpose, "A"), 1, 8);

                    $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET trans_id='" . $trans_id . "', msg='" .
                    $info->recipient_legal . "' WHERE hash = '" . $orderInfo['hash'] . "'";
                    Db::getInstance()->execute($sql);

                    $this->context->smarty->assign(array(
                        'duedate' => '',
                        'recipient_legal' => $info->recipient_legal,
                        'accountowner' => $info->transfer_payment_data->accountowner,
                        'iban' => $info->transfer_payment_data->iban,
                        'bic' => $info->transfer_payment_data->bic,
                        'bankname' => $info->transfer_payment_data->bankname,
                        'purpose' => $info->transfer_payment_data->purpose,
                        'qr_link' => $info->payment_qr_image_url,
                        'payment_link' => $info->payment_link
                    ));
                }
                if ($orderInfo['payment_type'] === 'prepay') {
                    $info = $api_res->data->opt;
                    $trans_id = Tools::substr(strrchr($info->transfer_payment_data->purpose, "A"), 1, 8);

                    $sql = "UPDATE " . _DB_PREFIX_ . "secupay SET trans_id='" . $trans_id . "', msg='" .
                    $info->recipient_legal . "' WHERE hash = '" . $orderInfo['hash'] . "'";
                    Db::getInstance()->execute($sql);

                    $this->context->smarty->assign(array(
                        'duedate' => '',
                        'recipient_legal' => $info->recipient_legal,
                        'accountowner' => $info->transfer_payment_data->accountowner,
                        'iban' => $info->transfer_payment_data->iban,
                        'bic' => $info->transfer_payment_data->bic,
                        'bankname' => $info->transfer_payment_data->bankname,
                        'purpose' => $info->transfer_payment_data->purpose
                    ));
                }
            } else {
                die('There went something wrong invoice generating. Please contact customer support.');
            }


            $tpl = '';
            if ($this->module->isPaymentTypeOkay($orderInfo['payment_type'])) {
                $tpl = $orderInfo['payment_type'];
            }

            $res = $this->module->display($this->file, $tpl . '.tpl');

            return $res;
        }

        return false;
    }

    private function getOrderInfo($orderId)
    {
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "secupay` WHERE id_order=" . (int)$orderId;
        $res = Db::getInstance()->executeS($sql);
        if (isset($res[0])) {
            return $res[0];
        } else {
            return;
        }
    }
}
