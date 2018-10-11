<?php
/**
 * description
 *
 * @copyright        2018/7/10 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/10 上午11:19
 * @modified         2018/7/10 上午11:19
 */

namespace Models\Faq\Faq;

use Models\Base;
use Models\Faq\Faq;

class Description extends Base
{
    protected $table = 'faq_description';
    protected $guarded = [];
    protected $primaryKey = 'description_id';

    public function faq()
    {
        return $this->belongsTo(Faq::class);
    }

    public function scopeByLanguageId($query, $languageId)
    {
        return $query->where('language_id', $languageId);
    }
}