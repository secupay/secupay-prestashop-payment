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

class SecupayDisplayPDFInvoiceController
{
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
        $lang_sql = 'select * from ' . _DB_PREFIX_ . "lang where id_lang = '" . $this->context->cookie->__get('id_lang')
            . "'";
        $lang_res = Db::getInstance()
                      ->executeS($lang_sql);

        'de' === $lang_res[0]['iso_code'] ? $lng = 'de_DE' : $lng = 'en_US';

        $orderInfo = $this->getOrderInfo(Tools::getValue('id_order'));

        if (Tools::strlen($orderInfo['hash']) > 1) {
            $data = array(
                'apikey' => $orderInfo['apikey'],
                'hash'   => $orderInfo['hash'],
            );

            $requestData = array(
                'data' => $data,
            );

            $api = new secupay_api($requestData, 'status', 'application/json', false, $lng);

            $api_res = $api->request();

            if ('ok' === $api_res->status) {
                if ('invoice' === $orderInfo['payment_type']) {
                    $info     = $api_res->data->opt;
                    $trans_id = Tools::substr(
                        strrchr(
                            $info->transfer_payment_data->purpose,
                            'A'
                        ),
                        1,
                        8
                    );

                    $sql = 'UPDATE ' . _DB_PREFIX_ . "secupay SET trans_id='" . $trans_id . "', msg='"
                        . $info->recipient_legal . "' WHERE hash = '" . $orderInfo['hash'] . "'";
                    Db::getInstance()
                      ->execute($sql);

                    $this->context->smarty->assign(
                        array(
                            'duedate'         => '',
                            'recipient_legal' => $info->recipient_legal,
                            'accountowner'    => $info->transfer_payment_data->accountowner,
                            'iban'            => $info->transfer_payment_data->iban,
                            'bic'             => $info->transfer_payment_data->bic,
                            'bankname'        => $info->transfer_payment_data->bankname,
                            'purpose'         => $info->transfer_payment_data->purpose,
                            'qr_link'         => $info->payment_qr_image_url,
                            'payment_link'    => $info->payment_link,
                        )
                    );
                }
                if ('prepay' === $orderInfo['payment_type']) {
                    $info     = $api_res->data->opt;
                    $trans_id = Tools::substr(
                        strrchr(
                            $info->transfer_payment_data->purpose,
                            'A'
                        ),
                        1,
                        8
                    );

                    $sql = 'UPDATE ' . _DB_PREFIX_ . "secupay SET trans_id='" . $trans_id . "', msg='"
                        . $info->recipient_legal . "' WHERE hash = '" . $orderInfo['hash'] . "'";
                    Db::getInstance()
                      ->execute($sql);

                    $this->context->smarty->assign(
                        array(
                            'duedate'         => '',
                            'recipient_legal' => $info->recipient_legal,
                            'accountowner'    => $info->transfer_payment_data->accountowner,
                            'iban'            => $info->transfer_payment_data->iban,
                            'bic'             => $info->transfer_payment_data->bic,
                            'bankname'        => $info->transfer_payment_data->bankname,
                            'purpose'         => $info->transfer_payment_data->purpose,
                        )
                    );
                }
            } else {
                die('There went something wrong invoice generating. Please contact customer support.');
            }

            $tpl = '';
            if ($this->module->isPaymentTypeOkay($orderInfo['payment_type'])) {
                $tpl = $orderInfo['payment_type'];
            }

            $res = $this->module->display(
                $this->file,
                $tpl . '.tpl'
            );

            return $res;
        }

        return false;
    }

    /**
     * @param $orderId
     */

    /**
     * @param $orderId
     */
    private function getOrderInfo($orderId)
    {
        $order = new Order($orderId);
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'secupay` WHERE id_order=' . $order->id_cart;
        $res = Db::getInstance()
                 ->executeS($sql);
        if (isset($res[0])) {
            return $res[0];
        } else {
            return;
        }
    }
}
