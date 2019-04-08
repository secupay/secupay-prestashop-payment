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
<!--<div class="iframe_wrapper">
    <div id="iframe_wrapper_center">
        <iframe id="spiframe" src="{$iframesrc|escape:'htmlall':'UTF-8'}" width="100%" height="750" scrolling="auto" name="_top" frameborder="0"></iframe>
    </div>
</div>
-->

<button class="btn btn-success loadiframe" id="b1" data-src="iframe.html">Click here to open iFrame.</button>

<div id="secupay">
    <main>
        <iframe
                id="frame" frameborder="0" scrolling="auto" width="100%" height="550px"
                src="{$iframesrc|escape:'htmlall':'UTF-8'}"
        ></iframe>
    </main>
</div>
