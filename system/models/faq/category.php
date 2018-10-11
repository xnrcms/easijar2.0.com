<?php
/**
 * description
 *
 * @copyright        2018/7/9 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/9 下午3:17
 * @modified         2018/7/9 下午3:17
 */

namespace Models\Faq;

use Models\Base;
use Models\Faq\Category\Description;
use Models\Faq\Category\Store;

class Category extends Base
{
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'date_modified';
    public $timestamps = true;
    protected $table = 'faq_category';
    protected $primaryKey = 'category_id';
    protected $fillable = ['status', 'sort_order', 'parent_id'];

    public static function getCollection($data)
    {
        if (isset($data['sort'])) {
            if ($data['sort'] == 'name') {
                $data['sort'] = 'faq_category_description.' . $data['sort'];
            } else {
                $data['sort'] = 'faq_category.' . $data['sort'];
            }
        }

        $query = static::query()->leftJoin('faq_category_description', 'faq_category_description.category_id',
            '=', 'faq_category.category_id')
            ->where('language_id', current_language_id())
            ->select('faq_category.*', 'faq_category_description.*');

        if (isset($data['sort'])) {
            $query->orderBy($data['sort'], $data['order'])
                ->offset($data['offset'])
                ->limit($data['limit']);
        }

        return $query->get();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function descriptions()
    {
        return $this->hasMany(Description::class);
    }

    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    public function category()
    {
        return $this->hasOne(Description::class, 'category_id', 'parent_id')
            ->where('language_id', current_language_id());
    }

    public function faqs()
    {
        return $this->hasMany(Faq::class)->where('status', 1);
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
                return url()->link('faq/category/edit', $queries);
            case 'delete':
                return url()->link('faq/category/delete', $queries);
            case 'show':
                return url()->link('faq/category', array('faq_category_id' => $this->category_id));
        }
    }

}

