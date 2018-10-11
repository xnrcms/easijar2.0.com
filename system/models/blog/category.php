<?php

/**
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2017-12-29 11:29:44
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-01 16:57:35
 */

namespace Models\Blog;

use Models\Base;

class Category extends Base
{
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'date_modified';
    public $timestamps = true;
    protected $table = 'blog_category';
    protected $primaryKey = 'category_id';
    protected $fillable = ['status', 'top', 'sort_order'];

    public static function getCollection($data)
    {
        if ($data['sort'] == 'name') {
            $data['sort'] = 'blog_category_description.' . $data['sort'];
        } else {
            $data['sort'] = 'blog_category.' . $data['sort'];
        }

        return static::query()
            ->leftJoin('blog_category_description', 'blog_category_description.category_id', '=', 'blog_category.category_id')
            ->where('language_id', current_language_id())
            ->select('blog_category.*', 'blog_category_description.*')
            ->orderBy($data['sort'], $data['order'])
            ->offset($data['offset'])
            ->limit($data['limit'])
            ->get();
    }

    public function descriptions()
    {
        return $this->hasMany(Category\Description::class);
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'blog_post_to_category');
    }

    public function href($type)
    {
        $queries = [];
        foreach (request()->get as $key => $value) {
            if (!in_array($key, ['route'])) {
                $queries[$key] = $value;
            }
        }
        $queries['category_id'] = $this->category_id;

        switch ($type) {
            case 'edit':
                return url()->link('blog/category/edit', $queries);
            case 'delete':
                return url()->link('blog/category/delete', $queries);
            case 'show':
                return url()->link('blog/category', array('blog_category_id' => $this->category_id));
        }
    }
}
