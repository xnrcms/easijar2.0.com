<?php
/**
 * Authentication.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-01-18 12:05
 * @modified   2018-01-18 12:05
 */

namespace Models\Customer;

use Models\Base;
use Models\Customer;

class Authentication extends Base
{
    protected $table = 'customer_authentication';

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }
}
