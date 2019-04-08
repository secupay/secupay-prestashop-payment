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
{if isset($confirmation)}
    <div class="alert alert-success">{l s='Settings updated' mod='secupay'}
        <button type="button" class="close" data-dismiss="alert">×</button>
    </div>
{/if}

<div class="alert alert-info">
    <img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_logo.png" alt="Logo" title="" />
    <p>{l s='The most popular payment methods from a Hand.Die secupay AG is your reliable partner for online payment and payment processing in your online store.' mod='secupay'}  </p>
    <p>{l s='With our payment methods debit, credit card and purchase on account offer a payment mix that leaves no wishes unfulfilled with your customers.' mod='secupay'}  </p>
    <p>{l s='With secupay you settle payments quickly, securely and reliably. Our unique payment guarantee protects you against non-payment and expensive dunning.' mod='secupay'} </p>
    <p>{l s='The integration works with a few steps, in all major online shop systems.' mod='secupay'}  </p>
    <p>{l s='For more information and around Secupay and request your personal API Key on' mod='secupay'}
        <a
                target="_blank"
                href="https://www.secupay.ag/"
        ><b>{l s='www.secupay.ag' mod='secupay'}</b></a>
    </p>

</div>

<form action="" method="post">
    <div class="panel">
        <fieldset class="level1">
            <legend>
                <img width="16" src="../modules/secupay/logo.gif" alt="Logo" />
                <b>{l s='API-Key Configuration' mod='secupay'}</b>
            </legend>
            <div class="form-wrapper">
                <div class="margin-form">
                    <label for="apikey" class="control-label col-lg-3">{l s='API-Key' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <input
                                name="apikey" class="textarea-autosize" value="{$apikey|escape:'htmlall':'UTF-8'}"
                                type="text"
                        />
                        <p>{l s='Please pay attention to space! The API key you can request from our Customer Service.' mod='secupay'}</p>
                    </div>
                </div>
                <div class="margin-form">
                    <label for="demo" class="control-label col-lg-3">{l s='Modus :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="demo" class="fixed-width-xl">
                            <option {if $demo eq '1'}selected{/if} value="1"> {l s='Demo' mod='secupay'}</option>
                            <option {if $demo eq '0'}selected{/if} value="0"> {l s='Live' mod='secupay'}</option>
                        </select>
                        <p>{l s='In demo mode, no transactions are executed.' mod='secupay'}</p>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset class="level1">
            <legend>
                <img width="16" src="../modules/secupay/logo.gif" alt="Logo" />
                <b>{l s='General Settings' mod='secupay'}</b>
            </legend>
            <div class="form-wrapper">
                <div class="margin-form">
                    <label
                            for="order_state"
                            class="control-label col-lg-3"
                    >{l s='Orderstatus after successful payment :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="order_state" class="fixed-width-xl">
                            {foreach from=$orderstates item=state}
                                <option {if $state['id_order_state'] eq $orderstate} selected{/if}
                                        value={$state['id_order_state']|escape:'htmlall':'UTF-8'}>{$state['name']|escape:'htmlall':'UTF-8'}</option>
                            {/foreach}
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="sendinvoicenumberauto"
                            class="control-label col-lg-3"
                    >{l s='Account number instead of No.:' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="sendinvoicenumberauto" class="fixed-width-xl">
                            <option
                                    {if $sendinvoicenumberauto eq '1'}selected{/if}
                                    value="1"
                            >{l s='Yes' mod='secupay'}</option>
                            <option
                                    {if $sendinvoicenumberauto eq '0'}selected{/if}
                                    value="0"
                            >{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="block_logo"
                            class="control-label col-lg-3"
                    >{l s='Payment block Left side :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="block_logo" class="fixed-width-xl">
                            <option {if $block_logo eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $block_logo eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset class="level1">
            <legend>
                <img width="16" src="../modules/secupay/logo.gif" alt="Logo" />
                <b>{l s='payment methods' mod='secupay'}</b>
            </legend>
            <div class="form-wrapper">
                <div class="margin-form">
                    <label
                            for="pay_cc"
                            class="control-label col-lg-3"
                    >{l s='Activate credit payment :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="pay_cc" class="fixed-width-xl" {if $pay_cc_enabled eq false}disabled{/if} >
                            <option {if $pay_cc eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $pay_cc eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label for="country_cc" class="control-label col-lg-3">{l s='Allowed countries' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <input
                                name="country_cc" class="textarea-autosize"
                                value="{$country_cc|escape:'htmlall':'UTF-8'}" type="text"
                        />
                        <p>{l s='Leave field blank for all countries, country separated by comma e.g. Germany, Spain (See Country Table).' mod='secupay'}</p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="pay_debit"
                            class="control-label col-lg-3"
                    >{l s='Activate direct debit payment :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="pay_debit" class="fixed-width-xl" {if $pay_debit_enabled eq false}disabled{/if}>
                            <option {if $pay_debit eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $pay_debit eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="country_debit"
                            class="control-label col-lg-3"
                    >{l s='Allowed countries' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <input
                                name="country_debit" class="textarea-autosize"
                                value="{$country_debit|escape:'htmlall':'UTF-8'}" type="text"
                        />
                        <p>{l s='Leave field blank for all countries, country separated by comma e.g. Germany, Spain (See Country Table).' mod='secupay'}</p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="pay_invoice"
                            class="control-label col-lg-3"
                    >{l s='Activate Purchase Orders :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select
                                name="pay_invoice" class="fixed-width-xl"
                                {if $pay_invoice_enabled eq false}disabled{/if}>
                            <option {if $pay_invoice eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $pay_invoice eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="country_invoice"
                            class="control-label col-lg-3"
                    >{l s='Allowed countries' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <input
                                name="country_invoice" class="textarea-autosize"
                                value="{$country_invoice|escape:'htmlall':'UTF-8'}" type="text"
                        />
                        <p>{l s='Leave field blank for all countries, country separated by comma e.g. Germany, Spain (See Country Table).' mod='secupay'}</p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="pay_sofort"
                            class="control-label col-lg-3"
                    >{l s='Activate pay now payment :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="pay_sofort" class="fixed-width-xl" {if $pay_sofort_enabled eq false}disabled{/if}>
                            <option {if $pay_sofort eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $pay_sofort eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="country_sofort"
                            class="control-label col-lg-3"
                    >{l s='Allowed countries' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <input
                                name="country_sofort" class="textarea-autosize"
                                value="{$country_debit|escape:'htmlall':'UTF-8'}" type="text"
                        />
                        <p>{l s='Leave field blank for all countries, country separated by comma e.g. Germany, Spain (See Country Table).' mod='secupay'}</p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="pay_prepay"
                            class="control-label col-lg-3"
                    >{l s='Activate Prepay Orders :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select
                                name="pay_prepay" class="fixed-width-xl"
                                {if $pay_invoice_enabled eq false}disabled{/if}>
                            <option {if $pay_prepay eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $pay_prepay eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="country_prepay"
                            class="control-label col-lg-3"
                    >{l s='Allowed countries' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <input
                                name="country_prepay" class="textarea-autosize"
                                value="{$country_prepay|escape:'htmlall':'UTF-8'}" type="text"
                        />
                        <p>{l s='Leave field blank for all countries, country separated by comma e.g. Germany, Spain (See Country Table).' mod='secupay'}</p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="cc_secure"
                            class="control-label col-lg-3"
                    >{l s='Creditcard disable at alternate delivery address :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select name="cc_secure" class="fixed-width-xl" {if $pay_cc_enabled eq false}disabled{/if}>
                            <option {if $cc_secure eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $cc_secure eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="debit_secure"
                            class="control-label col-lg-3"
                    >{l s='Debit disable at alternate delivery address :' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select
                                name="debit_secure" class="fixed-width-xl"
                                {if $pay_debit_enabled eq false}disabled{/if}>
                            <option {if $debit_secure eq '1'}selected{/if} value="1">{l s='Yes' mod='secupay'}</option>
                            <option {if $debit_secure eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
                <div class="margin-form">
                    <label
                            for="invoice_secure"
                            class="control-label col-lg-3"
                    >{l s='Disable accounting for deviating delivery address:' mod='secupay'}</label>
                    <div class="col-lg-9">
                        <select
                                name="invoice_secure" class="fixed-width-xl"
                                {if $pay_invoice_enabled eq false}disabled{/if}>
                            <option
                                    {if $invoice_secure eq '1'}selected{/if}
                                    value="1"
                            >{l s='Yes' mod='secupay'}</option>
                            <option {if $invoice_secure eq '0'}selected{/if} value="0">{l s='No' mod='secupay'}</option>
                        </select>
                        <p></p>
                    </div>
                </div>
            </div>
        </fieldset>
        <div class="panel-footer" align="right">
            <button
                    id="module_form_submit_btn" class="btn btn-default pull-right" name="secupay_pc_form" value="1"
                    type="submit"
            >
                <i class="process-icon-save"></i>{l s='Save' mod='secupay'}
            </button>
        </div>
    </div>
</form>