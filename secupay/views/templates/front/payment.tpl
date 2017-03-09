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
{capture name=path}
    {l s='secupay payment' mod='secupay'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='secupay'}
</h1>

{assign var='current_step' value='payment'}

{include file="$tpl_dir./order-steps.tpl"}
{if $nb_products <= 0}
<p class="alert alert-warning">
    {l s='Your shopping cart is empty.' mod='secupay'}
</p>
{else}
<form action="{$link->getModuleLink('secupay', 'validation', ['pt' => $pt], true)|escape:'html':'UTF-8'}" method="post">
    <div class="box cheque-box">
        <h3 class="page-subheading">
            {l s='secupay payment' mod='secupay'}
        </h3>
        <p class="cheque-indent">
            <strong class="dark">
                 {l s='You have chosen to pay with secupay' mod='secupay'}
                {if $pt=='creditcard'}
                    {l s='credit card' mod='secupay'}
                {/if}
                {if $pt=='invoice'}
                    {l s='invoice' mod='secupay'}
                {/if}
                {if $pt=='prepay'}
                    {l s='prepay' mod='secupay'}
                {/if}
                {if $pt=='debit'}
                    {l s='debit' mod='secupay'}
                {/if}. {l s='Here is a short summary of your order' mod='secupay'}:
            </strong>
        </p>
        <p>
            - {l s='The total amount of your order is' mod='secupay'}
            <span id="amount" class="price">{displayPrice price=$total_amount}</span>
            {if $use_taxes == 1}
                {l s='(tax incl.)' mod='secupay'}
            {/if}
        </p>
        
        <p>
            - {l s='secupay payment information will be displayed on the next page.' mod='secupay'}
            <br>
            - {l s='Please confirm your order by clicking "I confirm my order."' mod='secupay'}
        </p>
    </div>
    <p class="cart_navigation clearfix" id="cart_navigation">
        <a
                class="button-exclusive btn btn-default"
                href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}">
            <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='secupay'}
        </a>
        <button
                class="button btn btn-default button-medium"
                type="submit">
            <span>{l s='I confirm my order' mod='secupay'}<i class="icon-chevron-right right"></i></span>
        </button>
    </p>
  
</form>
{/if}
