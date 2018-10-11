<?php

namespace Models\Blog;

use Models\Base;

class Post extends Base
{
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'date_modified';
    public $timestamps = true;
    protected $table = 'blog_post';
    protected $primaryKey = 'post_id';
    protected $fillable = ['viewed', 'status', 'sort_order'];

    public static function getCollection($data)
    {
        if ($data['sort'] == 'name') {
            $data['sort'] = 'blog_post_description.' . $data['sort'];
        } else {
            $data['sort'] = 'blog_post.' . $data['sort'];
        }

        return static::query()
            ->leftJoin('blog_post_description', 'blog_post_description.post_id', '=', 'blog_post.post_id')
            ->where('language_id', current_language_id())
            ->select('blog_post.*', 'blog_post_description.*')
            ->orderBy($data['sort'], $data['order'])
            ->offset($data['offset'])
            ->limit($data['limit'])
            ->get();
    }

    public function descriptions()
    {
        return $this->hasMany(Post\Description::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'blog_post_to_category');
    }

    public function href($type)
    {
        switch ($type) {
            case 'edit':
                return url()->link('blog/post/edit', array('user_token' => session()->get('user_token'), 'post_id' => $this->post_id));
            case 'delete':
                return url()->link('blog/post/delete', array('user_token' => session()->get('user_token'), 'post_id' => $this->post_id));
            case 'show':
                return url()->link('blog/post', array('blog_post_id' => $this->post_id));
            case 'show_app':
                return url()->link('app/blog/post', array('blog_post_id' => $this->post_id));
        }
    }

    public function incrementViewCountBy($step = 1)
    {
        if ((int)$step < 1) {
            $step = 1;
        }
        $this->viewed += (int)$step;
        $this->save();
    }
}
