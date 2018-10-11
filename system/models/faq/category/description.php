<?php
/**
 * description
 *
 * @copyright        2018/7/9 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/9 下午3:50
 * @modified         2018/7/9 下午3:50
 */

namespace Models\Faq\Category;

use Models\Base;

class Description extends Base
{
    protected $table = 'faq_category_description';
    protected $primaryKey = 'description_id';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeByLanguageId($query, $languageId)
    {
        return $query->where('language_id', $languageId);
    }
}