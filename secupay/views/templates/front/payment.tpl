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
    <a
            href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}"
            title="{l s='Go back to the checkout' mod='secupay'}"
    >{l s='Checkout' mod='secupay'}</a>
    <span class="navigation-pipe">{$navigationPipe|escape:'html':'UTF-8'}</span>{l s='secupay payment' mod='secupay'}
{/capture}

<h1 class="page-heading">
    {l s='Order summary' mod='secupay'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h2 class="page-subheading">{l s='secupay payment' mod='secupay'}</h2>
{if $error_tpl}
    {include file=$error_tpl hideOrderSteps=true}
{/if}
{if $nb_products <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='secupay'}
    </p>
{else}
    <div id="secupay">
        <main>
            <iframe
                    id="frame" frameborder="0" scrolling="auto" width="100%" height="550px"
                    src="{$iframesrc|escape:'htmlall':'UTF-8'}"
            ></iframe>
        </main>
    </div>
{/if}