<?php
/**
 * description
 *
 * @copyright        2018/7/10 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/10 上午11:15
 * @modified         2018/7/10 上午11:15
 */

namespace Models\Faq;

use Models\Base;
use Models\Faq\Faq\Description;

class Faq extends Base
{
    const CREATED_AT = 'date_added';
    const UPDATED_AT = 'date_modified';
    public $timestamps = true;
    protected $table = 'faq';
    protected $primaryKey = 'faq_id';
    protected $fillable = ['status', 'category_id', 'sort_order'];

    public static function getCollection($data)
    {
        if ($data['sort'] == 'question') {
            $data['sort'] = 'faq_description.' . $data['sort'];
        } else {
            $data['sort'] = 'faq.' . $data['sort'];
        }

        $query = static::query()->leftJoin('faq_description', 'faq_description.faq_id', '=', 'faq.faq_id')
            ->where('language_id', current_language_id())
            ->select('faq.*', 'faq_description.*')
            ->orderBy($data['sort'], $data['order'])
            ->offset($data['offset'])
            ->limit($data['limit'])
            ->get();

        return $query;
    }

    public function active($query)
    {
        return $query->where('status', 1);
    }

    public function descriptions()
    {
        return $this->hasMany(Description::class);
    }

    public function category()
    {
        return $this->belongsTo(Category\Description::class, 'category_id', 'category_id')
            ->where('language_id', current_language_id());
    }

    public function href($type)
    {
        $queries = [];
        foreach (request()->get as $key => $value) {
            if (!in_array($key, ['route'])) {
                $queries[$key] = $value;
            }
        }
        $queries['faq_id'] = $this->faq_id;

        switch ($type) {
            case 'edit':
                return url()->link('faq/faq/edit', $queries);
            case 'delete':
                return url()->link('faq/faq/delete', $queries);
            case 'show':
                return url()->link('faq/faq', array('faq_id' => $this->faq_id));
        }
    }
}