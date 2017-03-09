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

class SecupayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * Validate order
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        // Check, if cart exists and all fields are set
        if ($cart->id_customer === 0 || $cart->id_address_delivery === 0
            || $cart->id_address_invoice === 0 || !$this->module->active
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Check, if module is enabled
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] === $this->module->name) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            Tools::redirect('index.php?controller=order&step=3');
        }
        // Check if customer exists
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        // Set datas
        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $extra_vars = array();
        $this->module->validateOrder(
            $cart->id,
            Configuration::get('SECUPAY_WAIT_FOR_CONFIRM'),
            $total,
            $this->module->displayName,
            null,
            $extra_vars,
            (int)$currency->id,
            false,
            $customer->secure_key
        );
        // Redirect on order confirmation page
        Tools::redirect(
            'index.php?controller=order-confirmation&id_cart=' . $cart->id .
            '&id_module=' . $this->module->id .
            '&id_order=' . $this->module->currentOrder .
            '&key=' . $customer->secure_key .
            '&pt=' . Tools::getValue('pt')
        );
    }
}
