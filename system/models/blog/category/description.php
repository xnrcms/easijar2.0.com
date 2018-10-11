<?php
/**
 * description.php
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Sam Chen <samchen@opencart.cn>
 * @created    2017-12-29 13:33
 * @modified   2017-12-29 13:33
 */

namespace Models\Blog\Category;

use Models\Base;
use Models\Blog\Category;

class Description extends Base
{
    protected $table = 'blog_category_description';
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
