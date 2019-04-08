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

use http\Cookie;

if ((bool) Configuration::get('PS_MOBILE_DEVICE')) {
    require_once _PS_MODULE_DIR_ . '/mobile_theme/Mobile_Detect.php';
}

// Retro 1.3, 'class_exists' cause problem with autoload...
if (version_compare(
    _PS_VERSION_,
    '1.4',
    '<'
)) {
    // Not exist for 1.3

    /**
     * Class Shop.
     */
    class Shop extends ObjectModel
    {
        /**
         * @var int
         */
        public $id = 1;
        /**
         * @var int
         */
        public $id_shop_group = 1;

        /**
         * Shop constructor.
         */
        public function __construct()
        {
        }

        /**
         * @return array
         */
        public static function getShops()
        {
            return array(
                array('id_shop' => 1, 'name' => 'Default shop'),
            );
        }

        /**
         * @return int
         */
        public static function getCurrentShop()
        {
            return 1;
        }
    }

    /**
     * Class Logger.
     */
    class Logger
    {
        /**
         * @param     $message
         * @param int $severity
         */
        public static function AddLog($message, $severity = 2)
        {
            $fp = fopen(
                dirname(__FILE__) . '/../logs.txt',
                'a+'
            );
            fwrite(
                $fp,
                '[' . (int) $severity . '] ' . Tools::safeOutput($message)
            );
            fclose($fp);
        }
    }
}

// Not exist for 1.3 and 1.4

/**
 * Class Context.
 */
class Context
{
    /**
     * @var Context
     */
    protected static $instance;

    /**
     * @var Cart
     */
    public $cart;

    /**
     * @var Customer
     */
    public $customer;

    /**
     * @var Cookie
     */
    public $cookie;

    /**
     * @var Link
     */
    public $link;

    /**
     * @var Country
     */
    public $country;

    /**
     * @var Employee
     */
    public $employee;

    /**
     * @var Controller
     */
    public $controller;

    /**
     * @var Language
     */
    public $language;

    /**
     * @var Currency
     */
    public $currency;

    /**
     * @var AdminTab
     */
    public $tab;

    /**
     * @var Shop
     */
    public $shop;

    /**
     * @var Smarty
     */
    public $smarty;

    /**
     * @ var Mobile Detect
     */
    public $mobile_detect;

    /**
     * @var bool|string mobile device of the customer
     */
    protected $mobile_device;

    /**
     * Context constructor.
     */
    public function __construct()
    {
        global $cookie, $cart, $smarty, $link;

        $this->tab = null;

        $this->cookie = $cookie;
        $this->cart   = $cart;
        $this->smarty = $smarty;
        $this->link   = $link;

        $this->controller = new ControllerBackwardModule();
        if (is_object($cookie)) {
            $this->currency = new Currency((int) $cookie->id_currency);
            $this->language = new Language((int) $cookie->id_lang);
            $this->country  = new Country((int) $cookie->id_country);
            $this->customer = new CustomerBackwardModule((int) $cookie->id_customer);
            $this->employee = new Employee((int) $cookie->id_employee);
        } else {
            $this->currency = null;
            $this->language = null;
            $this->country  = null;
            $this->customer = null;
            $this->employee = null;
        }

        $this->shop = new ShopBackwardModule();

        if ((bool) Configuration::get('PS_MOBILE_DEVICE')) {
            $this->mobile_detect = new Mobile_Detect();
        }
    }

    /**
     * @return int Shop context type (Shop::CONTEXT_ALL, etc.)
     */
    public static function shop()
    {
        if (!self::$instance->shop->getContextType()) {
            return ShopBackwardModule::CONTEXT_ALL;
        }

        return self::$instance->shop->getContextType();
    }

    /**
     * @return bool|string
     */
    public function getMobileDevice()
    {
        if (is_null($this->mobile_device)) {
            $this->mobile_device = false;
            if ($this->checkMobileContext()) {
                switch ((int) Configuration::get('PS_MOBILE_DEVICE')) {
                    case 0: // Only for mobile device
                        if ($this->mobile_detect->isMobile() && !$this->mobile_detect->isTablet()) {
                            $this->mobile_device = true;
                        }
                        break;
                    case 1: // Only for touchpads
                        if ($this->mobile_detect->isTablet() && !$this->mobile_detect->isMobile()) {
                            $this->mobile_device = true;
                        }
                        break;
                    case 2: // For touchpad or mobile devices
                        if ($this->mobile_detect->isMobile() || $this->mobile_detect->isTablet()) {
                            $this->mobile_device = true;
                        }
                        break;
                }
            }
        }

        return $this->mobile_device;
    }

    /**
     * @return bool
     */
    protected function checkMobileContext()
    {
        return isset($_SERVER['HTTP_USER_AGENT'])
            && (bool) Configuration::get('PS_MOBILE_DEVICE')
            && !Context::getContext()->cookie->no_mobile;
    }

    /**
     * Get a singleton context.
     *
     * @return Context
     */
    public static function getContext()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Context();
        }

        return self::$instance;
    }

    /**
     * Clone current context.
     *
     * @return Context
     */
    public function cloneContext()
    {
        return clone $this;
    }
}

/**
 * Class Shop for Backward compatibility.
 */
class ShopBackwardModule extends Shop
{
    const CONTEXT_ALL = 1;

    /**
     * @var int
     */
    public $id = 1;
    /**
     * @var int
     */
    public $id_shop_group = 1;

    /**
     * @return int
     */
    public function getContextType()
    {
        return ShopBackwardModule::CONTEXT_ALL;
    }

    // Simulate shop for 1.3 / 1.4

    /**
     * @return int
     */
    public function getID()
    {
        return 1;
    }

    /**
     * Get shop theme name.
     *
     * @return string
     */
    public function getTheme()
    {
        return _THEME_NAME_;
    }

    /**
     * @return bool
     */
    public function isFeatureActive()
    {
        return false;
    }
}

/**
 * Class Controller for a Backward compatibility
 * Allow to use method declared in 1.5.
 */
class ControllerBackwardModule
{
    /**
     * @param        $css_uri
     * @param string $css_media_type
     */
    public function addCSS($css_uri, $css_media_type = 'all')
    {
        Tools::addCSS(
            $css_uri,
            $css_media_type
        );
    }

    public function addJquery()
    {
        if (_PS_VERSION_ < '1.5') {
            $this->addJS(_PS_JS_DIR_ . 'jquery/jquery-1.4.4.min.js');
        } elseif (_PS_VERSION_ >= '1.5') {
            $this->addJS(_PS_JS_DIR_ . 'jquery/jquery-1.7.2.min.js');
        }
    }

    /**
     * @param $js_uri
     */
    public function addJS($js_uri)
    {
        Tools::addJS($js_uri);
    }
}

/**
 * Class Customer for a Backward compatibility
 * Allow to use method declared in 1.5.
 */
class CustomerBackwardModule extends Customer
{
    /**
     * @var bool
     */
    public $logged = false;

    /**
     * Check customer informations and return customer validity.
     *
     * @since 1.5.0
     *
     * @param bool $with_guest
     *
     * @return bool customer validity
     */
    public function isLogged($with_guest = false)
    {
        if (!$with_guest && 1 === $this->is_guest) {
            return false;
        }

        /* Customer is valid only if it can be load and if object password is the same as database one */
        if (1 === $this->logged && $this->id && Validate::isUnsignedId($this->id)
            && Customer::checkPassword(
                $this->id,
                $this->passwd
            )) {
            return true;
        }

        return false;
    }
}
