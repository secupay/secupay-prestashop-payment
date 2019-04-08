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

class SecupayValidateModuleFrontController extends ModuleFrontController
{
    /**
     *
     */
    public function postProcess()
    {
        PrestaShopLogger::addLog(
            'SecupayResponseModuleFrontController:initContent',
            1,
            null,
            'Secupay Plugin',
            null,
            true
        );
        $response     = $this->getResponse();
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        $key          = call_user_func_array(
            'base64_decode',
            array(Tools::getValue('secupay_mod'))
        );
        $result       = explode(
            '-',
            $key
        );
        if (5 === count($result) && 'SP' === $result[2]) {
            $cartID = (int) $result[1];
            $status = $result[3];
            $pt     = $result[4];
        }
        $cart = $this->context->cart;
        PrestaShopLogger::addLog(
            'SecupayResponseModuleFrontController:initContentResponse' . var_export(
                $response,
                true
            ),
            1,
            null,
            'Secupay Plugin',
            null,
            (int) $this->context->cart->id,
            true
        );
        PrestaShopLogger::addLog(
            'SecupayResponseModuleFrontController:initContentStatus' . var_export(
                $status,
                true
            ),
            1,
            null,
            'Secupay Plugin',
            null,
            (int) $this->context->cart->id,
            true
        );
        PrestaShopLogger::addLog(
            'SecupayResponseModuleFrontController:initContentPT' . var_export(
                $pt,
                true
            ),
            1,
            null,
            'Secupay Plugin',
            null,
            (int) $this->context->cart->id,
            true
        );
        if (($cartID !== (int) $this->context->cart->id) || 0 === $cart->id_customer || 0 === $cart->id_address_delivery
            || 0 === $cart->id_address_invoice
            || !$this->module->active) {
            if (version_compare(
                _PS_VERSION_,
                '1.7',
                '>='
            )) {
                $this->errors[] = '';
                $this->redirectWithNotifications(
                    $this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=1'
                );
            } else {
                $this->context->cookie->error_message = '';
                Tools::redirect($this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=1');
            }
        }
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            Tools::redirect('index.php?controller=order&step=3');
        }
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            if (version_compare(
                _PS_VERSION_,
                '1.7',
                '>='
            )) {
                $this->errors[] = '';
                $this->redirectWithNotifications(
                    $this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=1'
                );
            } else {
                $this->context->cookie->error_message = '';
                Tools::redirect($this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=1');
            }
        }
        if (true === Tools::getIsset('cancel') && 'cancel' === $status) {
            PrestaShopLogger::addLog(
                'SecupayResponseModuleFrontController:cancel' . var_export(
                    $status,
                    true
                ),
                1,
                null,
                'Secupay Plugin',
                (int) $this->context->cart->id,
                true
            );
            $sql = 'update ' . _DB_PREFIX_ . "secupay set status='denied' WHERE id_order = '" . $cartID
                . "' and payment_type = '" . $pt . "'";
            PrestaShopLogger::addLog(
                'SecupayResponseModuleFrontController:cancel:request:SQL' . print_r(
                    $sql,
                    true
                ),
                1,
                null,
                'Secupay Plugin',
                $cartID,
                true
            );
            Db::getInstance()
              ->execute($sql);
            if (version_compare(
                _PS_VERSION_,
                '1.7',
                '>='
            )) {
                $this->errors[] = '';
                $this->redirectWithNotifications(
                    $this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=3'
                );
            } else {
                $this->context->cookie->error_message = '';
                Tools::redirect($this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=3');
            }
        }

        if (true === Tools::getIsset('success') && 'success' === $status) {
            PrestaShopLogger::addLog(
                'SecupayResponseModuleFrontController:success' . var_export(
                    $status,
                    true
                ),
                1,
                null,
                'Secupay Plugin',
                (int) $this->context->cart->id,
                true
            );
            $total = (float) $cart->getOrderTotal(
                true,
                Cart::BOTH
            );
            PrestaShopLogger::addLog(
                'SecupayResponseModuleFrontController:success' . var_export(
                    $this->module->l($pt),
                    true
                ),
                1,
                null,
                'Secupay Plugin',
                (int) $this->context->cart->id,
                true
            );
            $extra_vars = array();
            if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                $moduleName = $this->module->getLangPayment('secupay.' . $pt, $pt);
            } else {
                $moduleName = $this->module->getLangPayment('secupay.' . $pt, $pt);
            }
            if ($this->module->validateOrder(
                $cart->id,
                Configuration::get('SECUPAY_WAIT_FOR_CONFIRM'),
                $total,
                $moduleName,
                null,
                $extra_vars,
                null,
                false,
                $customer->secure_key
            )) {
                PrestaShopLogger::addLog(
                    'SecupayResponseModuleFrontController:validateOrder:true',
                    1,
                    null,
                    'Secupay Plugin',
                    (int) $this->context->cart->id,
                    true
                );

                if (!empty($cart->id)) {
                    $order_id = Order::getOrderByCartId((int) $this->context->cart->id);
                    $order    = new Order($order_id);
                    $sql      = 'update ' . _DB_PREFIX_ . "secupay set unique_id = '" . $order->reference
                        . "' WHERE id_order = '" . $cart->id . "'";
                    PrestaShopLogger::addLog(
                        'SecupayResponseModuleFrontController:validateOrder:true:sql' . print_r(
                            $sql,
                            true
                        ),
                        1,
                        null,
                        'Secupay Plugin',
                        $cart->id,
                        true
                    );
                    Db::getInstance()
                      ->execute($sql);
                }
                if (version_compare(_PS_VERSION_, '1.7', '>=')) {
                    Tools::redirect(
                        'index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module='
                        . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key='
                        . $customer->secure_key . '&pt=' . $pt . '&success=' . $order->reference
                    );
                } else {
                    //todo Bugfix Redirect order-confirmation
                    Tools::redirect(
                        'index.php?controller=history&id_cart=' . $cart->id . '&id_module=' . $this->module->id
                        . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key . '&pt=' . $pt
                        . '&success=' . $order->reference
                    );
                }
            }
            PrestaShopLogger::addLog(
                'SecupayResponseModuleFrontController:validateOrder:false',
                1,
                null,
                'Secupay Plugin',
                (int) $this->context->cart->id,
                true
            );
            $message = $this->module->l('An error occurred while processing payment');
            if (version_compare(
                _PS_VERSION_,
                '1.7',
                '>='
            )) {
                $this->errors[] = $message;
                $this->redirectWithNotifications(
                    $this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=3'
                );
            } else {
                $this->context->cookie->error_message = $message;
                Tools::redirect($this->context->shop->getBaseURL(true) . 'index.php?controller=order&step=3');
            }
        }
    }

    /**
     * @return mixed
     */
    protected function getResponse()
    {
        return Tools::getAllValues();
    }
}
