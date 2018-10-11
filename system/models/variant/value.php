<?php
/**
 * value.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-07 16:41
 * @modified   2018-05-07 16:41
 */

namespace Models\Variant;

use Models\Base;
use Models\Variant\Value\Description as ValueDescription;

class Value extends Base
{
    protected $table = 'variant_value';
    protected $primaryKey = 'variant_value_id';

    public function getForeignKey()
    {
        return 'variant_value_id';
    }

    public function scopeJoinVariantValueDescription($query)
    {
        $query->join('variant_value_description as vvd', 'vvd.variant_value_id', '=', 'variant_value.variant_value_id')
            ->where('vvd.language_id', '=', current_language_id())
            ->orderBy('variant_value.sort_order', 'asc');
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
        return $this->hasMany(ValueDescription::class);
    }
}
