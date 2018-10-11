<?php
/**
 * seourl.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Sam Chen <samchen@opencart.cn>
 * @created    2018-01-08 20:04
 * @modified   2018-01-08 20:04
 */


namespace Models;


class SeoUrl extends Base
{
    protected $table = 'seo_url';
    protected $primaryKey = 'seo_url_id';
    protected $fillable = ['store_id', 'language_id', 'query', 'keyword'];

    public static function allKeywords($type, $id)
    {
        return static::where('query', "{$type}_id={$id}")->pluck('keyword', 'language_id');
    }

    public static function deleteAllKeywords($type, $id)
    {
        return static::where('query', "{$type}_id={$id}")->delete();
    }

    public static function available($keyword, $excludeType, $excludeId)
    {
        $clauses = [];
        $clauses[] = ['keyword', '=', $keyword];
        if (!empty($excludeType) && (int)$excludeId > 0) {
            $clauses[] = ['query', '<>', "{$excludeType}_id={$excludeId}"];
        }

        return static::where($clauses)->count() > 0 ? false : true;
    }

    public static function createKeyword($type, $id, $keyword, $languageId)
    {
        if (empty($type) || (int)$id < 1 || empty($keyword) || (int)$languageId < 1) {
            return;
        }

        $data = array(
            'language_id' => $languageId,
            'store_id'    => 0,
            'query'       => "{$type}_id={$id}",
            'keyword'     => $keyword
        );
        static::create($data);
    }
}
