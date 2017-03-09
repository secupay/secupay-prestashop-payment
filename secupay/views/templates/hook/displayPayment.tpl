{**
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
 *}
 {if $pay_cc}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module"><a href="{$link->getModuleLink('secupay', 'payment', ['pt' => 'creditcard'])|escape:'html':'UTF-8'}" class="secupay_cc">
               <img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_creditcard.png" alt="{l s='Pay with secupay credit card' mod='secupay'}" title="{l s='Pay with secupay credit card' mod='secupay'}" width="255" height="71" />
               {l s='Pay with secupay credit card' mod='secupay'}
            </a></p>
     </div>
</div>
{/if}
{if $pay_debit}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module"><a href="{$link->getModuleLink('secupay', 'payment', ['pt' => 'debit'])|escape:'html':'UTF-8'}" class="secupay_debit">
                     <img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_debit.png" alt="{l s='Pay with secupay debit' mod='secupay'}" title="{l s='Pay with secupay debit' mod='secupay'}" width="255" height="71" />
                    {l s='Pay with secupay debit' mod='secupay'}
                </a></p>
        </div>
    </div>
{/if} 
{if $pay_invoice}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module"><a href="{$link->getModuleLink('secupay', 'payment', ['pt' => 'invoice'])|escape:'html':'UTF-8'}" class="secupay_invoice">
                     <img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_invoice.png" alt="{l s='Pay with secupay invoice' mod='secupay'}" title="{l s='Pay with secupay invoice' mod='secupay'}" width="255" height="71" />
                     {l s='Pay with secupay invoice' mod='secupay'}
                </a></p>
        </div>
    </div>
{/if}
{if $pay_prepay}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module"><a href="{$link->getModuleLink('secupay', 'payment', ['pt' => 'prepay'])|escape:'html':'UTF-8'}" class="secupay_prepay">
                     <img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_prepay.png" alt="{l s='Pay with secupay prepay' mod='secupay'}" title="{l s='Pay with secupay prepay' mod='secupay'}" width="255" height="71" />
                     {l s='Pay with secupay prepay' mod='secupay'}
                </a></p>
        </div>
    </div>
{/if}
