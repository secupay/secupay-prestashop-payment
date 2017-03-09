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
 <p>{l s='Your selected payment method' mod='secupay'}: {l s='secupay.prepay' mod='secupay'}<br>
{l s='Shipping after payment.</p>' mod='secupay'}
<p>{l s='The invoice amount is to' mod='secupay'} {$recipient_legal|escape:'htmlall':'UTF-8'} {l s='worn' mod='secupay'}.<br>
<strong>{l s='A payment with discharging effect can only be to the following account' mod='secupay'}:</strong></p>
<p><table border="0" width="100%">
<tr>
<td width="80%">
{l s='receiver' mod='secupay'}: {$accountowner|escape:'htmlall':'UTF-8'}<br>
{l s='IBAN' mod='secupay'}: {$iban|escape:'htmlall':'UTF-8'}, {l s='BIC' mod='secupay'}: {$bic|escape:'htmlall':'UTF-8'}<br>
{l s='Bank' mod='secupay'}: {$bankname|escape:'htmlall':'UTF-8'}<br>
{l s='Usage' mod='secupay'}: {$purpose|escape:'htmlall':'UTF-8'}<br>
</td>
</tr>
</table></p>
