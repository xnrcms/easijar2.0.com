<?php
/**
 * togroup.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-18 17:49
 * @modified   2018-05-18 17:49
 */

namespace Models\Variant;

use Models\Base;

class ToGroup extends Base
{
    protected $table = 'variant_to_group';
    protected $fillable = ['variant_id', 'variant_group_id'];

    public function getCurrentKeyName()
    {
        return 'id';
    }
}
