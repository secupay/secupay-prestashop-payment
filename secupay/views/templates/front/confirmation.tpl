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
<p>{l s='Your order on' mod='secupay'} <span class="bold">{$shop_name|escape:'htmlall':'UTF-8'}</span> {l s='is complete.' mod='secupay'}
	<br /><br />
	{l s='Thank you for your Order.' mod='secupay'}
	<br /><br /><span class="bold">{l s='Your order will be sent very soon.' mod='secupay'}</span>
	<br /><br />{l s='For any questions or for further information, please contact our' mod='secupay'} <a href="{$link->getPageLink('contact-form.php', true)|escape:'htmlall':'UTF-8'}">{l s='customer support' mod='secupay'}</a>.
</p>

