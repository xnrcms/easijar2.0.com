<?php
/**
 * description.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Sam Chen <samchen@opencart.cn>
 * @created    2018-01-03 9:37
 * @modified   2018-01-03 9:37
 */

namespace Models\Blog\Post;

use Models\Base;

class Description extends Base
{
    protected $table = 'blog_post_description';
    protected $primaryKey = 'post_id';
    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function scopeByLanguageId($query, $languageId)
    {
        return $query->where('language_id', $languageId);
    }
}
