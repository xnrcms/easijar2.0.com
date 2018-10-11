<?php
/**
 * variant.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-07 16:20
 * @modified   2018-05-07 16:20
 */

namespace Models\Product;

use Models\Base;

class Variant extends Base
{
    protected $table = 'product_variant';
    protected $primaryKey = 'product_variant_id';
    protected $fillable = ['product_id', 'variant_id', 'variant_value_id', 'value_name', 'value_image'];

    public function variant()
    {
        return $this->belongsTo(\Models\Variant::class, 'variant_id');
    }

    public function variantValue()
    {
        return $this->belongsTo(\Models\Variant\Value::class, 'variant_value_id');
    }

    public function scopeWithDescription($query, $productIds = [])
    {
        $query->join('variant as v', 'v.variant_id', '=', 'product_variant.variant_id')
            ->join('variant_value as vv', 'vv.variant_value_id', '=', 'product_variant.variant_value_id')
            ->join('variant_value_description as vvd', 'vvd.variant_value_id', '=', 'vv.variant_value_id')
            ->where('vvd.language_id', '=', current_language_id())
            ->orderBy('v.sort_order')
            ->orderBy('vv.sort_order');
        if ($productIds) {
            $query->whereIn('product_id', $productIds);
        }
        return $query;
    }

    public function variantName()
    {
        return $this->variant->name();
    }

    public function variantValueName()
    {
        return $this->variantValue->name();
    }


    public function scopeByVariantId($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function formatValue($resize = true)
    {
        $image = $this->value_image ? $this->value_image : $this->image;
        return array(
            'variant_value_id' => $this->variant_value_id,
            'name' => $this->value_name ? $this->value_name : $this->name,
            'image' => $image ? ($resize ? image_resize($image) : $image) : ''
        );
    }
}
