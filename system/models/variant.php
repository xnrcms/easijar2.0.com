<?php
/**
 * variant.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-07 16:24
 * @modified   2018-05-07 16:24
 */

namespace Models;

use Models\Variant\Description;
use Models\Variant\Group;
use Models\Variant\ToGroup;
use Models\Variant\Value;

class Variant extends Base
{
    protected $table = 'variant';
    protected $primaryKey = 'variant_id';

    public static function getVariantValues()
    {
        $allVariants = self::joinVariantDescription()->get();
        $allVariants->map(function ($variant) {
            $variant->values = $variant->values()->joinVariantValueDescription()->get();
        });
        return $allVariants;
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'variant_group_id');
    }

    public function variantRelation()
    {
        return $this->hasMany(ToGroup::class, 'variant_id');
    }

    public function name()
    {
        $descriptions = $this->descriptions()->where('language_id', current_language_id());
        if ($descriptions->count()) {
            $description = $descriptions->first();
        } else {
            $description = $this->descriptions()->first();
        }
        return $description->name;
    }

    public function descriptions()
    {
        return $this->hasMany(Description::class);
    }

    public function getValueData()
    {
        $values = [];
        if ($this->values->isEmpty()) {
            return $values;
        }

        $variantValues = $this->values()->joinVariantValueDescription()->get();
        foreach ($variantValues as $value) {
            $values[] = array(
                'variant_value_id' => $value->variant_value_id,
                'name' => $value->name,
                'image' => $value->image ? image_resize($value->image) : ''
            );
        }
        return $values;
    }

    public function values()
    {
        return $this->hasMany(Value::class);
    }

    public function scopeJoinVariantDescription($query)
    {
        $query->join('variant_description as vd', 'vd.variant_id', '=', 'variant.variant_id')
            ->where('vd.language_id', '=', current_language_id())
            ->orderBy('variant.sort_order', 'asc');
    }
}
