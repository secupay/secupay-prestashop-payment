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

class AdminOrdersController extends AdminOrdersControllerCore
{
    /**
     * AdminOrdersController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        PrestaShopLogger::addLog(
            'AdminOrdersController:__construct',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        //require functions library
        require_once '../modules/secupay/lib/secupay_api.php';

        if (Configuration::get('SECUPAY_API')) {
            $this->_join                       .= 'LEFT JOIN ' . _DB_PREFIX_ . 'secupay ct ON
            ct.id_order=a.id_cart and ct.status != "denied"';
            $this->_select                     .= ', ct.payment_type as `payment_type`';
            $this->_select                     .= ', ct.v_send as `v_send`';
            $this->_select                     .= ', ct.v_status as `v_status`';
            $this->_select                     .= ', ct.searchcode as `searchcode`';
            $this->_select                     .= ', ct.id_secupay as `id_secupay`';
            $this->_select                     .= ', ct.hash as `hash`';
            $this->_select                     .= ', ct.apikey as `apikey`';
            $this->_select                     .= ', ct.carrier_code as `carrier_code`';
            $this->fields_list['payment_type'] = array(
                'title'          => $this->l('Send Shipped to Secupay'),
                'align'          => 'center',
                'orderby'        => false,
                'search'         => false,
                'callback'       => 'sendShipIcons',
                'remove_onclick' => true,
            );
        }
    }

    /**
     * @return string
     */
    public function postProcess()
    {
        PrestaShopLogger::addLog(
            'AdminOrdersController:postProcess',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        if (Configuration::get('SECUPAY_API')) {
            parent::postProcess();
            $order = new Order(Tools::getValue('id_order'));
            if (!Validate::isLoadedObject($order)) {
                return '';
            }
            $sql    = 'SELECT hash, v_send, v_status,payment_type,searchcode,track_number,carrier_code, unique_id
                      FROM ' . _DB_PREFIX_ . "secupay WHERE id_order = '" . $order->id_cart . "'
                      and status != 'denied'";
            $status = Db::getInstance()
                        ->getRow($sql);
            PrestaShopLogger::addLog(
                'AdminOrdersController:postProcess:v_send' . print_r(
                    $status['v_send'],
                    true
                ),
                1,
                null,
                'Secupay Plugin',
                $order->id,
                true
            );
            // is not send?
            $hash = $status['hash'];
            if (1 !== $status['v_send']) {
                // Infos not OK
                PrestaShopLogger::addLog(
                    'AdminOrdersController:postProcess:v_status' . print_r(
                        $status['v_status'],
                        true
                    ),
                    1,
                    null,
                    'Secupay Plugin',
                    $order->id,
                    true
                );
                if (1 !== $status['v_status']
                    || (empty($status['searchcode'])
                        || empty($status['track_number'])
                        || empty($status['carrier_code']))) {
                    PrestaShopLogger::addLog(
                        'AdminOrdersController:postProcess:payment_type' . print_r(
                            $status['payment_type'],
                            true
                        ),
                        1,
                        null,
                        'Secupay Plugin',
                        $order->id,
                        true
                    );
                    // if secupay Payment and Shippingnumber
                    if (in_array($status['payment_type'], array('creditcard', 'invoice', 'debit', 'sofort', 'prepay'))
                        && $order->shipping_number) {
                        $sql = 'SELECT name FROM ' . _DB_PREFIX_ . "carrier WHERE id_carrier = '" . $order->id_carrier
                            . "'";
                        PrestaShopLogger::addLog(
                            'AdminOrdersController:postProcess:SQL' . print_r(
                                $sql,
                                true
                            ),
                            1,
                            null,
                            'Secupay Plugin',
                            $order->id_cart,
                            true
                        );
                        //select Carrier and Shipping
                        $carrier = Db::getInstance()
                                     ->getRow($sql);
                        $carrier = self::getCarrier(
                            $order->shipping_number,
                            $carrier['name']
                        );
                        // select Searchcode
                        if (Configuration::get('SECUPAY_SENDINVOICENUMBERAUTO') and $order->invoice_number) {
                            $searchCode   = $order->invoice_number;
                            $updateStatus = 1;
                        } else {
                            $searchCode   = $order->id_cart;
                            $updateStatus = 1;
                        }
                        if (Configuration::get('SECUPAY_SENDINVOICENUMBERAUTO') and empty($order->invoice_number)) {
                            $updateStatus = 0;
                        }
                        if (empty($carrier) || empty($order->delivery_number) || empty($order->shipping_number)) {
                            $updateStatus = 0;
                        }
                        //update Carrier,ShippingNumber and SearchCode
                        $sql = 'UPDATE ' . _DB_PREFIX_ . "secupay SET v_status='" . $updateStatus . "', track_number='"
                            . $order->shipping_number . "', carrier_code='" . $carrier . "', searchcode='" . $searchCode
                            . "' WHERE id_order = '" . $order->id_cart . "' and hash = '" . $hash . "'";
                        PrestaShopLogger::addLog(
                            'AdminOrdersController:postProcess:Update:SQL' . print_r(
                                $sql,
                                true
                            ),
                            1,
                            null,
                            'Secupay Plugin',
                            $order->id_cart,
                            true
                        );
                        Db::getInstance()
                          ->execute($sql);
                    }

                    $status['v_status'] = $updateStatus;
                }
                if (1 === $status['v_status']) {
                    $sql = 'SELECT hash,apikey,track_number,carrier_code,searchcode,payment_type FROM ' . _DB_PREFIX_
                        . "secupay WHERE id_order = '" . $order->id_cart . "' and status != 'denied'";
                    PrestaShopLogger::addLog(
                        'AdminOrdersController:postProcess:v_status_1:SQL' . print_r(
                            $sql,
                            true
                        ),
                        1,
                        null,
                        'Secupay Plugin',
                        $order->id_cart,
                        true
                    );
                    $vstatus                      = Db::getInstance()
                                                      ->getRow($sql);
                    $data                         = array();
                    $data['apikey']               = $vstatus['apikey'];
                    $data['hash']                 = $vstatus['hash'];
                    $data['tracking']['provider'] = $vstatus['carrier_code'];
                    $data['tracking']['number']   = $vstatus['track_number'];
                    $data['invoice_number']       = $vstatus['searchcode'];
                    $requestData                  = array();
                    $requestData['data']          = $data;
                    $capture                      = self::getPayment($vstatus['payment_type']);
                    $api                          = new secupay_api(
                        $requestData,
                        $capture,
                        'application/json',
                        false,
                        'de_DE'
                    );
                    $api_res                      = $api->request();
                    if ('ok' === $api_res->status
                        || 'Zahlung konnte nicht abgeschlossen werden' === utf8_decode($api_res->errors[0]->message)) {
                        $sql = 'UPDATE ' . _DB_PREFIX_ . "secupay SET v_send='1'
                        WHERE id_order = '" . $order->id_cart . "'";
                        Db::getInstance()
                          ->execute($sql);
                    }
                }
            }
        }
    }

    /**
     * @param $payment_type
     * @param $tr
     *
     * @return string
     */

    /**
     * @param $trackingnumber
     * @param $provider
     *
     * @return string
     */
    public function getCarrier($trackingnumber, $provider)
    {
        if (preg_match(
            "/^1Z\s?[0-9A-Z]{3}\s?[0-9A-Z]{3}\s?[0-9A-Z]{2}\s?[0-9A-Z]{4}\s?[0-9A-Z]{3}\s?[0-9A-Z]$/i",
            $trackingnumber
        )) {
            $resprovider = 'UPS';
        } elseif (preg_match("/^\d{14}$/", $trackingnumber)) {
            $resprovider = 'HLG';
        } elseif (preg_match("/^\d{11}$/", $trackingnumber)) {
            $resprovider = 'GLS';
        } elseif (preg_match("/[A-Z]{3}\d{2}\.?\d{2}\.?(\d{3}\s?){3}/", $trackingnumber)
            || preg_match("/[A-Z]{3}\d{2}\.?\d{2}\.?\d{3}/", $trackingnumber)
            || preg_match("/(\d{12}|\d{16}|\d{20})/", $trackingnumber)) {
            $resprovider = 'DHL';
        } elseif (preg_match("/RR\s?\d{4}\s?\d{5}\s?\d(?=DE)/", $trackingnumber)
            || preg_match("/NN\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE(\s)?\d{3})/", $trackingnumber)
            || preg_match("/RA\d{9}(?=DE)/", $trackingnumber)
            || preg_match("/LX\d{9}(?=DE)/", $trackingnumber)
            || preg_match("/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/", $trackingnumber)
            || preg_match("/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/", $trackingnumber)
            || preg_match("/XX\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/", $trackingnumber)
            || preg_match("/RG\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/", $trackingnumber)) {
            $resprovider = 'DPAG';
        } else {
            $resprovider = $provider;
        }

        return $resprovider;
    }

    /**
     * @param $trackingnumber
     * @param $provider
     *
     * @return string
     */

    /**
     * @param $payment
     *
     * @return string
     */
    public function getPayment($payment)
    {
        if ('invoice' === $payment) {
            return 'capture';
        } else {
            return 'adddata';
        }
    }


    /**
     * @param $payment_type
     * @param $tr
     *
     * @return string
     */
    public function sendShipIcons($payment_type, $tr)
    {
        $order = new Order($tr['id_order']);
        PrestaShopLogger::addLog(
            'AdminOrdersController:sendShipIcons:Paymenttype' . print_r(
                $payment_type,
                true
            ),
            1,
            null,
            'Secupay Plugin',
            $order->id_cart,
            true
        );
        PrestaShopLogger::addLog(
            'AdminOrdersController:sendShipIcons:tr' . print_r(
                $tr,
                true
            ),
            1,
            null,
            'Secupay Plugin',
            $order->id_cart,
            true
        );
        if (!Validate::isLoadedObject($order)) {
            return '';
        }
        $sql     = 'SELECT name FROM ' . _DB_PREFIX_ . "carrier WHERE id_carrier = '" . $order->id_carrier . "'";
        $carrier = Db::getInstance()
                     ->getRow($sql);
        $carrier = self::getCarrier(
            $order->shipping_number,
            $carrier['name']
        );
        if ($order->delivery_number and $order->shipping_number and !$tr['carrier_code']) {
            $sql = 'UPDATE ' . _DB_PREFIX_ . "secupay SET track_number='" . $order->shipping_number . "',
            carrier_code='" . $carrier . "' WHERE hash = '" . $tr['hash'] . "'";
            Db::getInstance()
              ->execute($sql);
        }
        $this->context->smarty->assign(
            array(
                'hash'            => $tr['hash'],
                'key'             => $tr['apikey'],
                'carrier_code'    => $tr['carrier_code'],
                'shipping_number' => $order->shipping_number,
                'invoice_number'  => $order->invoice_number,
                'delivery_number' => $order->delivery_number,
            )
        );
        if ('invoice' !== $payment_type) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_na.tpl');
        }
        if (!empty($tr['v_send'])) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_ok.tpl');
        }
        if ('invoice' === $payment_type && empty($tr['v_send'])) {
            return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_icon.tpl');
        }

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'secupay/views/templates/admin/_send_ship_na.tpl');
    }
}
