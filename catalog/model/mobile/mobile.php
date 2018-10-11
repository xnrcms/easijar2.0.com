<?php

/**
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-01-12 09:43:42
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-01-12 09:45:33
 */

class ModelMobileMobile extends Model
{
    public function getCategoryMobileImage($category_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "category_mobile WHERE category_id = '" . (int)$category_id . "'");
        if ($query->num_rows) {
            return $query->row['image'];
        }
        return null;
    }
}
