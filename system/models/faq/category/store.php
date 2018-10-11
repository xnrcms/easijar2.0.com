<?php
/**
 * description
 *
 * @copyright        2018/7/9 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/9 下午4:54
 * @modified         2018/7/9 下午4:54
 */

namespace Models\Faq\Category;

use Models\Base;

class Store extends Base
{
    protected $table = 'faq_category_to_store';
    protected $primaryKey = 'category_id';
    protected $guarded = [];
}
