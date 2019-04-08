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

//todo Cart / PUSh id
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/secupay.php';
PrestaShopLogger::addLog(
    'SecupayPUSH:__construct',
    1,
    null,
    'Secupay Plugin',
    null,
    true
);
$response           = '';
$hash               = Tools::getValue('hash');
$status_id          = Tools::getValue('status_id');
$status_description = Tools::getValue('status_description');
$changed            = Tools::getValue('changed');
$payment_status     = Tools::getValue('payment_status');
$apikey             = Tools::getValue('apikey');
$hint               = Tools::getValue('hint');
$status             = 0;
$_html              = '';

$my_apikey = Configuration::get('SECUPAY_API');

if (ip2long($_SERVER['REMOTE_ADDR']) < ip2long('91.195.151.255')
    && ip2long('91.195.150.0') < ip2long($_SERVER['REMOTE_ADDR'])) {
    PrestaShopLogger::addLog(
        'Secupay:Push:adress:ok',
        1,
        null,
        'Secupay Plugin',
        null,
        true
    );
    if ($my_apikey = $apikey) {
        $secupay     = new Secupay();
        $new_history = new OrderHistory();
        $key         = call_user_func_array(
            'base64_decode',
            array(Tools::getValue('secupay_mod'))
        );
        $result      = explode(
            '-',
            $key
        );
        PrestaShopLogger::addLog(
            'Secupay:Push:adress:apiKey',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        if (5 === count($result) && 'SP' === $result[2]) {
            PrestaShopLogger::addLog(
                'Secupay:Push:adress:result',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            $cartID   = (int) $result[1];
            $order_id = Order::getOrderByCartId($cartID);
            $order    = new Order($order_id);
            PrestaShopLogger::addLog(
                'Secupay:Push:adress:order',
                1,
                null,
                'Secupay Plugin',
                null,
                true
            );
            if (0 !== $order_id) {
                $new_history->id_order   = $order_id;
                $order_status_waiting    = Configuration::get('SECUPAY_WAIT_FOR_CONFIRM');
                $order_status_accepted   = Configuration::get('SECUPAY_PAYMENT_CONFIRMED');
                $order_status_denied     = Configuration::get('SECUPAY_PAYMENT_DENIED');
                $order_status_issue      = Configuration::get('SECUPAY_PAYMENT_ISSUE');
                $order_status_void       = Configuration::get('SECUPAY_PAYMENT_VOID');
                $order_status_authorized = Configuration::get('SECUPAY_PAYMENT_AUTHORIZED');
                $order_status_default    = Configuration::get('SECUPAY_DEFAULT_ORDERSTATE');

                $history      = new OrderHistory($order_id);
                $order_status = $history->id_order_state;
                $sql          = 'SELECT * FROM ' . _DB_PREFIX_ . "secupay WHERE hash = '" . $hash . "'";

                $res = Db::getInstance()
                         ->executeS($sql);
                if (1 === count($res) and 'accepted' !== $res[0]['status'] and 'denied' !== $res[0]['status']) {
                    $payment_type = $res[0]['payment_type'];
                } else {
                    $payment_status = '';
                }
                PrestaShopLogger::addLog(
                    'Secupay:Push:adress:payment_type' . print_r(
                        $payment_type,
                        true
                    ),
                    1,
                    null,
                    'Secupay Plugin',
                    $cartID,
                    true
                );
                PrestaShopLogger::addLog(
                    'Secupay:Push:adress:payment_status' . print_r(
                        $payment_status,
                        true
                    ),
                    1,
                    null,
                    'Secupay Plugin',
                    $cartID,
                    true
                );
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
                        if ('prepay' === $payment_type) {
                            $order_status = -99;
                        }
                        if ('sofort' === $payment_type) {
                            $order_status = $order_status_accepted;
                        }
                        break;
                    default:
                        $response     = 'ack=Disapproved&error=payment_status+not+supported';
                        $order_status = -99;
                        break;
                }
                PrestaShopLogger::addLog(
                    'Secupay:Push:adress:order_status' . print_r(
                        $order_status,
                        true
                    ),
                    1,
                    null,
                    'Secupay Plugin',
                    $cartID,
                    true
                );
                if (-99 !== $order_status) {
                    try {
                        $data                = array();
                        $data['apikey']      = $my_apikey;
                        $data['hash']        = $hash;
                        $requestData         = array();
                        $requestData['data'] = $data;
                        $api                 = new secupay_api($requestData, 'status', 'application/json', '');
                        $api_res             = $api->request();
                        $trans_id            = $api_res->data->trans_id;
                        $status              = $api_res->data->status;
                        $amount              = (int) $api_res->data->amount;
                        $amount_total        = number_format($order->getOrdersTotalPaid(), 2, '.', ',') * 100;
                        if (strcmp($amount_total, $amount) !== 0) {
                            $order_status = $order_status_issue;
                        }

                        if (!empty($trans_id)) {
                            $sql = 'update ' . _DB_PREFIX_ . "secupay set trans_id='" . $trans_id . "',status='"
                                . $payment_status . "' WHERE hash = '" . $hash . "'";
                            Db::getInstance()
                              ->execute($sql);
                        }
                        if ($order_status === $order_status_accepted) {
                            $new_history->changeIdOrderState(
                                $order_status_default,
                                $order_id,
                                true
                            );
                            $new_history->add(true);
                            if (!empty($trans_id)) {
                                $order = new Order($order_id);

                                $payments                    = $order->getOrderPaymentCollection();
                                $payments[0]->transaction_id = $trans_id;
                                $payments[0]->payment_method = $secupay->getLangPayment(
                                    'secupay.' . $payment_type,
                                    $payment_type
                                );
                                $payments[0]->update();
                            }
                        } else {
                            $new_history->changeIdOrderState(
                                $order_status,
                                $order_id,
                                true
                            );
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
    PrestaShopLogger::addLog(
        'Secupay:Push:adress:Disapproved',
        1,
        null,
        'Secupay Plugin',
        null,
        true
    );
    $response = 'ack=Disapproved&error=request+invalid';
}
echo $response . '&' . http_build_query($_POST);
