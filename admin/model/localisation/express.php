<?php
/**
 * exoress.php
 *
 * @copyright 2018 opencart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author StifferChen <chenlin@opencart.cn>
 * @created 2018-06-04 15:17
 * @modified 2018-06-04 15:17
 */

class ModelLocalisationExpress extends Model
{
    public function install()
    {
        $this->db->query("
            create table if not exists " .  DB_PREFIX . "express(
                express_id int unsigned not null primary key auto_increment,
                status tinyint(1) not null default 0
            )Engine=MyISAM default charset utf8;
        ");

        $this->db->query("
            create table if not exists " .  DB_PREFIX . "express_title(
                express_title_id int unsigned not null primary key auto_increment,
                express_id int unsigned not null default 0,
                language_id int unsigned not null default 0,
                title varchar(240) not null default ''
            )Engine=MyISAM default charset utf8;
        ");
    }

    public function addExpress($data)
    {
        $this->db->query("insert into " . DB_PREFIX . "express set status=" . (int)$data['status']);
        $express_id = $this->db->getLastId();

        foreach ($data['express'] as $language_id => $title) {
            $this->db->query("insert into " . DB_PREFIX . "express_title set express_id=" . (int)$express_id . ", language_id=" . (int)$language_id . ", title='" . $title['title'] . "'");
        }
    }

    public function getTotalExpresses()
    {
        $row = $this->db->query("select count(*) total from " . DB_PREFIX . "express")->row;
        return $row ? $row['total'] : 0;
    }

    public function getExpresses($data = array())
    {
        $sql = "select e.*, et.title from " . DB_PREFIX . "express e left join " . DB_PREFIX . "express_title et on e.express_id = et.express_id where et.language_id=" . (int)$this->config->get('config_language_id') . " order by e.express_id desc";
        if ($data) {
            $sql .= " limit {$data['start']}, {$data['limit']}";
        }
        return $this->db->query($sql)->rows;
    }

    public function getExpressExpress($express_id)
    {
        $results = array();
        $rows = $this->db->query("select * from " . DB_PREFIX . "express_title where express_id=" . (int)$express_id)->rows;
        if ($rows) {
            foreach ($rows as $row) {
                $results[$row['language_id']]['title'] = $row['title'];
            }
        }
        return $results;
    }

    public function getExpress($express_id)
    {
        return $this->db->query("select * from " . DB_PREFIX . "express where express_id=" . (int)$express_id)->row;
    }

    public function editExpress($express_id, $data)
    {
        $this->db->query("update " . DB_PREFIX . "express set status=" . (int)$data['status'] . " where express_id=" . (int)$express_id);
        $this->db->query("delete from " . DB_PREFIX . "express_title where express_id=" . (int)$express_id);
        foreach ($data['express'] as $language_id => $title) {
            $this->db->query("insert into " . DB_PREFIX . "express_title set express_id=" . (int)$express_id . ", language_id=" . (int)$language_id . ", title='" . $title['title'] . "'");
        }
    }

    public function deleteExpress($express_id)
    {
        $this->db->query("delete from " . DB_PREFIX . "express where express_id=" . (int)$express_id);
        $this->db->query("delete from " . DB_PREFIX . "express_title where express_id=" . (int)$express_id);
    }
}