<?php
/**
 * msseller.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-06-06 10:47
 * @modified 2018-06-06 10:47
 */
namespace Seller;

class MsSeller
{
    private static $instance = null;
    private $registry = null;
    private $seller_id = '';
    private $group_id = '';
    private $store_name = '';
    private $company = '';
    private $description = '';
    private $country_id = '';
    private $zone_id = '';
    private $city_id = '';
    private $county_id = '';
    private $avatar = '';
    private $banner = '';
    private $alipay = '';
    private $product_validation = '';
    private $status = '';
    private $date_added = '';
    private $date_modified = '';
    private $is_seller = false;

    private function __construct($registry = null)
    {
        $this->registry = $registry;
        $this->seller_id = $this->customer->getId();

        if ($this->seller_id) {
            $row = $this->db->query("select * from " . DB_PREFIX . "ms_seller where seller_id=" . (int)$this->seller_id)->row;
            if ($row) {
                $this->group_id = $row['seller_group_id'];
                $this->store_name = $row['store_name'];
                $this->company = $row['company'];
                $this->description = $row['description'];
                $this->country_id = $row['country_id'];
                $this->zone_id = $row['zone_id'];
                $this->city_id = $row['city_id'];
                $this->county_id = $row['county_id'];
                $this->avatar = $row['avatar'];
                $this->banner = $row['banner'];
                $this->alipay = $row['alipay'];
                $this->product_validation = $row['product_validation'];
                $this->status = $row['status'];
                $this->date_added = $row['date_added'];
                $this->date_modified = $row['date_modified'];
                $this->is_seller = true;
            }
        }
    }

    private function __clone()
    {

    }

    public static function getInstance($registry = null)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($registry);
        }

        return self::$instance;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function sellerId()
    {
        return $this->seller_id;
    }

    public function groupId()
    {
        return $this->group_id;
    }

    public function storeName()
    {
        return $this->store_name;
    }

    public function company()
    {
        return $this->company;
    }

    public function description()
    {
        return $this->description;
    }

    public function countryId()
    {
        return $this->country_id;
    }

    public function zoneId()
    {
        return $this->zone_id;
    }

    public function cityId()
    {
        return $this->city_id;
    }

    public function countyId()
    {
        return $this->county_id;
    }

    public function avatar()
    {
        return $this->avatar;
    }

    public function banner()
    {
        return $this->banner;
    }

    public function alipay()
    {
        return $this->alipay;
    }

    public function productValidation()
    {
        return $this->product_validation;
    }

    public function status()
    {
        return $this->status;
    }

    public function dateAdded()
    {
        return $this->date_added;
    }

    public function dateModified()
    {
        return $this->date_modified;
    }

    public function isSeller()
    {
        return $this->is_seller;
    }
}