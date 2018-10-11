<?php
/**
 * product.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-16 10:17
 * @modified   2018-05-16 10:17
 */

namespace Models\Order;

use Models\Base;

class Product extends Base
{
    protected $table = 'order_product';
    protected $primaryKey = 'order_product_id';

    public function createOrderVariants($variants)
    {
        if (empty($variants)) {
            return;
        }
        foreach ($variants as $variant) {
            $this->orderVariants()->create(array(
                'order_id' => $this->order_id,
                'product_variant_id' => $variant['product_variant_id'],
                'name' => $variant['name'],
                'value' => $variant['value']
            ));
        }
    }

    public function orderVariants()
    {
        return $this->hasMany(\Models\Order\Variant::class, 'order_product_id');
    }

    public function getVariantLabels($type = 'array')
    {
        $labels = $labelData = [];
        foreach ($this->orderVariants as $opVariant) {
            $labels[] = $opVariant->value;
            $labelData[] = array(
                'product_variant_id' => $opVariant->product_variant_id,
                'name' => $opVariant->name,
                'value' => $opVariant->value
            );
        }
        if ($type == 'array') {
            return $labelData;
        } elseif ($type == 'string') {
            return $labels ? implode('/', $labels) : '';
        }
    }
}