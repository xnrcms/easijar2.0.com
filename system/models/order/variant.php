<?php
/**
 * variant.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-16 09:56
 * @modified   2018-05-16 09:56
 */

namespace Models\Order;

use Models\Base;

class Variant extends Base
{
    protected $table = 'order_variant';
    protected $primaryKey = 'order_variant_id';

    protected $fillable = [
        'order_id', 'order_product_id', 'product_variant_id', 'product_variant_value_id', 'name', 'value'
    ];
}