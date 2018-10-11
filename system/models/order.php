<?php
/**
 * order.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-16 09:48
 * @modified   2018-05-16 09:48
 */

namespace Models;

class Order extends Base
{
    protected $table = 'order';
    protected $primaryKey = 'order_id';

    public function orderVariants()
    {
        return $this->hasMany(\Models\Order\Variant::class);
    }
}