<?php
/**
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_1_2($object)
{
    Db::getInstance()->execute(
        'ALTER TABLE `'._DB_PREFIX_.
        'secupay` CHANGE `date_add` `timestamp` 
        TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
    );
    Db::getInstance()->execute(
        'ALTER TABLE `'._DB_PREFIX_.
        'secupay` CHANGE `request` `req_data` 
        TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL'
    );
    Db::getInstance()->execute(
        'ALTER TABLE `'._DB_PREFIX_.
        'secupay` CHANGE `response` `ret_data` 
        TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL'
    );
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `unique_id` varchar(255) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `rank` int(10) UNSIGNED default \'0\'');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `status` varchar(255) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `trans_id` int(10) UNSIGNED default \'0\'');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `msg` varchar(255) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `amount` varchar(255) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `updated` datetime default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `created` datetime default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `v_status` varchar(20) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `v_send` varchar(1) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `track_number` varchar(255) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `track_send` varchar(1) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `carrier_code` varchar(32) default NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'secupay` ADD `searchcode` varchar(255) default NULL');
    return true;
}
