<?php
/**
 * Customer.php
 *
 * We can use \Models\Customer::all()->toArray() or \Models\Customer::find(1) now
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2017-12-05 20:54
 * @modified   2017-12-05 20:54
 */

namespace Models;

use Models\Customer\Authentication;

class Customer extends Base
{
    protected $table = 'customer';
    protected $primaryKey = 'customer_id';

    public function authentications()
    {
        return $this->hasMany(Authentication::class);
    }
}