<?php

/**
 * flash_sale.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2018-01-15 10:48
 * @modified 2018-01-15 10:48
 */
class ModelExtensionModuleFlashSale extends Model
{
    public function install()
    {
        $this->db->query("create table if not exists " . DB_PREFIX . "flash_sale_product(
            `flash_sale_product_id` int unsigned not null primary key auto_increment,
            `product_id` int unsigned not null default 0,
            `order_id` int unsigned not null default 0,
            count int unsigned not null default 0,
            date_added timestamp not null
        )engine=MyISAM default charset utf8;");
    }

    public function uninstall()
    {
        $this->db->query("drop table if EXISTS " . DB_PREFIX . "flash_sale_product");
    }
}