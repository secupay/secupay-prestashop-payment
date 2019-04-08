{*
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement.
*
* You must not modify, adapt or create derivative works of this source code
*
*  @author    D3 Data Development
*  @copyright 2017 D3 Data Development
*  @license   LICENSE.txt
*}
{extends file='checkout/checkout.tpl'}
{block name="content"}
    <section id="content">
        <div class="row">
            <div class="col-md-12">
                <section id="checkout-payment-step" class="checkout-step -current -reachable js-current-step">

                    <main>
                        <iframe
                                id="frame" frameborder="0" scrolling="auto" width="100%" height="550px"
                                src="{$iframesrc|escape:'htmlall':'UTF-8'}"
                        ></iframe>
                    </main>
                </section>
            </div>
        </div>
    </section>
{/block}
