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
{if Configuration::get('SECUPAY_SENDSHIPPINGAUTO')} 
  <span class="btn-group-action">
    <span class="btn-group">
      <p class="btn btn-default _blank" >{l s='Auto Report' mod='secupay'}</p>
    </span>
</span>
{else}
 <span class="btn-group-action">
    <span class="btn-group">
    
    <a class="btn btn-default _blank" href="https://api.secupay.ag/payment/{$hash|escape:'htmlall':'UTF-8'}/capture/{$key|escape:'htmlall':'UTF-8'}">
            {l s='Report Shipping' mod='secupay'}
        </a>
        </span>
</span>
{/if}