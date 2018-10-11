<?php
/**
 * base.php
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2017-12-20 20:15
 * @modified   2017-12-20 20:15
 */

namespace Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Base extends Model
{
    public $timestamps = false;
    protected $modelName = '';

    public function __construct(array $attributes = [])
    {
        if (!$this->table) {
            $this->setTable($this->getCurrentClassName());
        }

        if ($this->primaryKey == 'id') {
            $this->setKeyName($this->getPrimaryName());
        }
        $this->modelName = str_replace('\\', '', snake_case(static::class));
        parent::__construct($attributes);
    }

    public function getCurrentClassName()
    {
        return snake_case(class_basename($this));
    }

    public function getPrimaryName()
    {
        return $this->getTable() . '_id';
    }

    public function getForeignKey()
    {
        return Str::snake(class_basename($this)) . '_id';
    }

    public function primaryValue()
    {
        return $this->{$this->getPrimaryName()};
    }

    public function getAllFields()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    public static function boot()
    {
        self::creating(function ($row) {
            $table = $row->getTable();
            if (\Schema::hasColumn($table, 'created_at')) {
                $row->created_at = Carbon::now()->toDateTimeString();
            }
            if (\Schema::hasColumn($table, 'date_added')) {
                $row->date_added = Carbon::now()->toDateTimeString();
            }

            if (\Schema::hasColumn($table, 'updated_at')) {
                $row->updated_at = Carbon::now()->toDateTimeString();
            }
            if (\Schema::hasColumn($table, 'date_modified')) {
                $row->date_modified = Carbon::now()->toDateTimeString();
            }
        });

        self::saving(function ($row) {
            $table = $row->getTable();
            if (\Schema::hasColumn($table, 'updated_at')) {
                $row->updated_at = Carbon::now()->toDateTimeString();
            }
            if (\Schema::hasColumn($table, 'date_modified')) {
                $row->date_modified = Carbon::now()->toDateTimeString();
            }
        });
    }

    public function localizedDescription($language_id = 0)
    {
        if ((int)$language_id < 1) {
            $language_id = current_language_id();
        }
        $description = $this->descriptions()->byLanguageId($language_id)->first();
        if (empty($description)) {
            $description = $this->descriptions()->first();
        }
        return $description;
    }

    public function html($property)
    {
        return html_entity_decode($this->{$property}, ENT_QUOTES, 'UTF-8');
    }
}
