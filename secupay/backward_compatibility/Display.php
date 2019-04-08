<?php
/**
 * secupay Payment Module.
 *
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 *
 * @category  Payment
 *
 * Description:
 *  Prestashop Plugin for integration of secupay AG payment services
 */

/**
 * Class allow to display tpl on the FO.
 */
class BWDisplay extends FrontController
{
    // Assign template, on 1.4 create it else assign for 1.5

    /**
     * @param $template
     */
    public function setTemplate($template)
    {
        if (_PS_VERSION_ >= '1.5') {
            parent::setTemplate($template);
        } else {
            $this->template = $template;
        }
    }

    // Overload displayContent for 1.4

    public function displayContent()
    {
        parent::displayContent();

        echo Context::getContext()->smarty->fetch($this->template);
    }
}
