<?php
/**
 * group.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-18 17:06
 * @modified   2018-05-18 17:06
 */

namespace Models\Variant;

use Models\Base;
use Models\Variant;

class Group extends Base
{
    protected $table = 'variant_group';
    protected $fillable = ['name'];

    public static function getAllGroups()
    {
        $variantGroups = self::with('variants')->get();
        if (!$variantGroups->count()) {
            return [];
        }

        $groups = [];
        foreach ($variantGroups as $index => $variantGroup) {
            $group = array(
                'group_id' => $variantGroup->variant_group_id,
                'name' => $variantGroup->name,
                'variants' => $variantGroup->variants->pluck('variant_id')->toArray()
            );
            $groups[$index] = $group;
        }
        return $groups;
    }

    public static function createGroup($postData)
    {
        $name = array_get($postData, 'name');
        $variantIds = array_get($postData, 'variant_ids');
        if (!trim($name) || !is_string($name)) {
            throw new \Exception("Invalid variant group name");
        } elseif (empty($variantIds)) {
            throw new \Exception("Invalid variant ids");
        }

        $group = self::create(array('name' => $name));
        foreach ($variantIds as $variantId) {
            $variant = Variant::find($variantId);
            if (!$variant) {
                continue;
            }

            $toGroup = new ToGroup();
            $toGroup->fill(array(
                'variant_id' => $variantId,
                'variant_group_id' => $group->variant_group_id
            ))->save();
        }
        return $group;
    }

    public function getForeignKey()
    {
        return 'variant_group_id';
    }

    public function variantRelation()
    {
        return $this->hasMany(ToGroup::class, 'variant_group_id');
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'variant_to_group');
    }
}