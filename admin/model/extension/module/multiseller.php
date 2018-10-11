<?php
class ModelExtensionModuleMultiseller extends Model {
	public function install() {
		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_seller_group` (
            `seller_group_id` int(11) NOT NULL AUTO_INCREMENT,
            `fee_show_flat` decimal(15,2) default 0,  /* 展示费固定值 */
            `fee_show_percent` decimal(15,2) default 0,  /* 展示费百分比 */
            `fee_sale_flat` decimal(15,2) default 0,  /* 销售费固定值 */
            `fee_sale_percent` decimal(15,2) default 0,  /* 销售费百分比 */
            `product_quantity` int(5) DEFAULT 0,  /* 商家最多添加的商品数量，0不限制 */
            `sort_order` int(5) DEFAULT 0,
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`seller_group_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_seller_group_description` (
            `seller_group_description_id` int(11) NOT NULL AUTO_INCREMENT,
            `seller_group_id` int(11) NOT NULL default 0,
            `name` varchar(32) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `language_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`seller_group_description_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_seller` (
            `seller_id` int(11) NOT NULL,  /* 实际就是customer_id，该表与customer表的记录一对一关系 */
            `seller_group_id` int(11) NOT NULL DEFAULT '1',
            `store_name` varchar(32) NOT NULL DEFAULT '',
            `company` varchar(32) NOT NULL DEFAULT '',
            `description` text NOT NULL,
            `country_id` int(11) NOT NULL DEFAULT '0',
            `zone_id` int(11) NOT NULL DEFAULT '0',
            `city_id` int unsigned not null default 0,
            `county_id` int unsigned not null default 0,
            `avatar` varchar(255) DEFAULT NULL,
            `banner` varchar(255) DEFAULT NULL,
            `alipay` varchar(255) DEFAULT NULL,
            `product_validation` tinyint(4) NOT NULL DEFAULT '1',
            `status` tinyint NOT NULL,  /* 商家状态, 0禁用，1启用 */
            `date_added` datetime NOT NULL,
            `date_modified` datetime NOT NULL,
            PRIMARY KEY (`seller_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_product_seller` (
            `product_id` int(11) NOT NULL,
            `seller_id` int(11) DEFAULT NULL default 0,
            `number_sold` int(11) NOT NULL DEFAULT '0',  /* 商家商品销售数量 */
            `approved` tinyint NOT NULL default 0, /* 顾客创建的商品需要审核, 0代表未审核，1代表已审核 */
		    `date_until` date NOT NULL DEFAULT '0000-00-00',  /* 下架日期 */
            PRIMARY KEY (`product_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_order_product` (
            `order_product_id` int(11) NOT NULL,
            `order_id` int(11) NOT NULL,
            `seller_id` int(11) DEFAULT NULL,
            `store_commission_flat` decimal(15,4) NOT NULL default 0,
            `store_commission_percent` decimal(15,4) NOT NULL default 0,
            `seller_amount` decimal(15,4) NOT NULL default 0,  /* 该订单商家得到的收入 */
            PRIMARY KEY (`order_product_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ms_order_total`(
            order_total_id INT(11) NOT NULL,
            seller_id int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`order_total_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ms_suborder` (
            `suborder_id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL default 0,
            `seller_id` int(11) NOT NULL default 0,
            `total` decimal(15,4) NOT NULL default 0,
            `order_status_id` int(11) NOT NULL default 0,
            PRIMARY KEY (`suborder_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ms_suborder_history` (
            `suborder_history_id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL default 0,
            `seller_id` int(11) NOT NULL default 0,
            `order_status_id` int(11) NOT NULL default 0,
            `comment` text NOT NULL,
            `notify` tinyint NOT NULL,
            `date_added` datetime NOT NULL default '0000-00-00',
            PRIMARY KEY (`suborder_history_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
            ALTER TABLE `" . DB_PREFIX . "order_shippingtrack`
            ADD `seller_id` int(11) NOT NULL;
		");

		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_seller_transaction` (
            `seller_transaction_id` int(11) NOT NULL AUTO_INCREMENT,
            `seller_id` int(11) NOT NULL default 0,
            `order_product_id` int(11) DEFAULT NULL,  /* 销售订单获取时 */
            `order_id` int(11) DEFAULT NULL,  /* 销售订单获取时 */
            `product_id` int(11) DEFAULT NULL,  /* 商品上架费时 */
            `withdraw_id` int(11) DEFAULT NULL,  /* 如果是提现，记录提现id */
            `amount` decimal(15,4) NOT NULL default 0,
            `description` text NOT NULL,
            `date_added` datetime NOT NULL,
            PRIMARY KEY (`seller_transaction_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
		CREATE TABLE if not exists `" . DB_PREFIX . "ms_withdraw` (
            `withdraw_id` int(11) NOT NULL AUTO_INCREMENT,
            `seller_id` int(11) NOT NULL default 0,
            `payment_status` int(11) NOT NULL default 0,
            `amount` decimal(15,4) NOT NULL default 0,
            `currency_id` int(11) NOT NULL default 0,
            `currency_code` varchar(3) NOT NULL default '',
            `description` text NOT NULL,
            `date_added` datetime NOT NULL,
            `date_paid` datetime NOT NULL,
            PRIMARY KEY (`withdraw_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
        CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ms_shipping_cost` (
            shipping_cost_id int(11) NOT NULL AUTO_INCREMENT,
            seller_id int(11) NOT NULL DEFAULT 0,
            title varchar(120) NOT NULL DEFAULT '',
            geo_zone_id int(11) NOT NULL DEFAULT 0,
            `type` tinyint NOT NULL DEFAULT 0,
            unit_weight tinyint NOT NULL DEFAULT 0,  /* 重量单位 */
            unit_volume tinyint NOT NULL DEFAULT 0,  /* 体积单位 */
            initial decimal(15,4) NOT NULL default 0,   /* 起价计量 */
            initial_cost decimal(15,4) NOT NULL default 0,  /* 起价金额 */
            `continue` decimal(15,4) NOT NULL default 0,   /* 续货计量 */
            continue_cost decimal(15,4) NOT NULL default 0,  /* 续量价格 */
            `sort_order` int(5) DEFAULT 0,
            PRIMARY KEY (`shipping_cost_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
		");

		$this->db->query("
            ALTER TABLE `" . DB_PREFIX . "return`
            ADD `seller_id` int(11) NOT NULL;
		");

		$this->db->query("INSERT INTO `" . DB_PREFIX . "ms_seller_group` (`fee_show_flat`, `fee_show_percent`, `fee_sale_flat`, `fee_sale_percent`, `product_quantity`, `sort_order`, `date_added`, `date_modified`) VALUES ('2', '3', '5', '3', '30', '1', NOW(), NOW());");
        $seller_group_id = $this->db->getLastId();
        $this->load->model('localisation/language');
        $languages = $this->model_localisation_language->getLanguages();
        foreach ($languages as $language) {
		    $this->db->query("INSERT INTO `" . DB_PREFIX . "ms_seller_group_description` (`seller_group_id`, `name`, `description`, `language_id`) VALUES ('" . (int)$seller_group_id . "', '默认组', '默认组', '" . (int)$language['language_id'] . "');");
        }
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_seller_group`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_seller_group_description`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_seller`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_product_seller`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_order_product`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_order_total`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_suborder`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_suborder_history`");
		$this->db->query("
            ALTER TABLE `" . DB_PREFIX . "order_shippingtrack`
            DROP COLUMN `seller_id`;
		");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_seller_transaction`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_withdraw`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ms_shipping_cost`");
		$this->db->query("
            ALTER TABLE `" . DB_PREFIX . "return`
            DROP COLUMN `seller_id`;
		");
	}
}
