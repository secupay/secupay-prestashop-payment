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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @return bool
 */
function upgrade_module_0_1_2()
{
    Db::getInstance()
      ->execute(
          'ALTER TABLE `' . _DB_PREFIX_ . 'secupay` CHANGE `date_add` `timestamp`
        TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
      );
    Db::getInstance()
      ->execute(
          'ALTER TABLE `' . _DB_PREFIX_ . 'secupay` CHANGE `request` `req_data`
        TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL'
      );
    Db::getInstance()
      ->execute(
          'ALTER TABLE `' . _DB_PREFIX_ . 'secupay` CHANGE `response` `ret_data`
        TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL'
      );
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `unique_id` varchar(255) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `rank` int(10) UNSIGNED default \'0\'');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `status` varchar(255) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `trans_id` int(10) UNSIGNED default \'0\'');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `msg` varchar(255) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `amount` varchar(255) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `updated` datetime default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `created` datetime default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `v_status` varchar(20) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `v_send` varchar(1) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `track_number` varchar(255) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `track_send` varchar(1) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `carrier_code` varchar(32) default NULL');
    Db::getInstance()
      ->execute('ALTER TABLE `' . _DB_PREFIX_ . 'secupay` ADD `searchcode` varchar(255) default NULL');

    return true;
}
