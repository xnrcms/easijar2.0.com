<?php
class ModelCatalogHandle extends Model {

    public function getProducts($filter_data)
    {
        $fields         = format_find_field('product_id,parent_id','p');
        $fields         .= ',' . format_find_field('name,language_id,description','pd');
        $language_id    = isset($filter_data['language_id']) ? $filter_data['language_id'] : 0;
        $parent_id      = isset($filter_data['parent_id']) ? $filter_data['parent_id'] : 0;
        $product_id     = isset($filter_data['product_id']) ? $filter_data['product_id'] : 0;

        $sql = "SELECT " . $fields . " FROM `oc_product` p LEFT JOIN `oc_product_description` pd ON p.product_id = pd.product_id WHERE pd.language_id = '" . (int)$language_id . "' AND p.parent_id = '" . (int)$parent_id . "' AND p.product_id > '" . $product_id . "'";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " ORDER BY product_id ASC LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getProductsTotals($filter_data)
    {
        $language_id    = isset($filter_data['language_id']) ? $filter_data['language_id'] : 0;
        $parent_id      = isset($filter_data['parent_id']) ? $filter_data['parent_id'] : 0;
        $product_id     = isset($filter_data['product_id']) ? $filter_data['product_id'] : 0;

        $sql = "SELECT COUNT(*) AS total FROM " . get_tabname('product') . " p LEFT JOIN " . get_tabname('product_description') . " pd ON p.product_id = pd.product_id WHERE pd.language_id = '" . (int)$language_id . "' AND p.parent_id = '" . (int)$parent_id . "' AND p.product_id > '" . $product_id . "'";
        $query = $this->db->query($sql);
        return $query->row['total'];
    }

    public function get_product_description_totals()
    {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product_description WHERE language_id = 1");
        return $query->row['total'];
    }

    public function get_product_count($parent_id = 0)
    {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product WHERE status = '1' AND parent_id = '" .$parent_id. "'");
        return $query->row['total'];
    }

    public function get_product_description_for_product_id($product_id,$language_id){
        $query = $this->db->query("SELECT name FROM ".DB_PREFIX . "product_description WHERE language_id = '".$language_id."' AND product_id = '".$product_id."'");
        return $query->row;
    }

    public function get_product_list($filter_data,$parent_id = 0)
    {

        $product_id     = isset($filter_data['product_id']) ? $filter_data['product_id'] : 0;
        $sql    = "SELECT `product_id` FROM " . DB_PREFIX . "product WHERE status = '1' AND product_id > '" . $product_id . "' AND parent_id = '" .$parent_id. "'";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " ORDER BY product_id ASC LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function get_product_status0()
    {
        $model  = file_get_contents('./1.txt');
        $model  = explode("\n", $model);

        $models = [];
        foreach ($model as $key => $value) {
            $models[] = trim($value);
        }

        $product_ids = ['3201','3880','3296','4014','4351','4442','4443','5161','5179','5308','5329','5706','13220','13300','14016','14017','14018'];

        /*$sql = "SELECT `product_id` FROM " . DB_PREFIX . "product WHERE status = '0' OR product_id < 1927 OR product_id IN ('" . implode("','",$product_ids) . "') OR model IN ('" .implode("','",$models). "')";*/

        $sql = "SELECT `product_id` FROM " . DB_PREFIX . "product WHERE model IN ('" .implode("','",$models). "')";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function add_product_image($data)
    {
        if (!empty($data)) {
            $sql    = "INSERT INTO " . DB_PREFIX . "product_image (product_id,image,sort_order) VALUES ";
            foreach ($data as $value) {
                $sql .= "('" . (int)$value['product_id'] . "','" . $value['image'] . "','0'),";
            }

            $sql        = trim($sql,',');

            $this->db->query($sql);
        }
    }

    public function get_product_images($product_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");
        return $query->rows;
    }

    public function get_product_status1()
    {
        $query = $this->db->query("SELECT `product_id` FROM " . DB_PREFIX . "product_description WHERE description LIKE '%data:image/jpeg;base64%'");
        return $query->rows;
    }

    public function get_product_status3($product_ids)
    {
        $query = $this->db->query("SELECT `product_id` FROM " . DB_PREFIX . "product WHERE product_id IN ('" . implode("','",$product_ids) . "')");
        return $query->rows;
    }

    public function get_product_description22()
    {
        $query = $this->db->query("SELECT `product_id` FROM " . DB_PREFIX . "product_description WHERE description LIKE '%data:image/jpeg;base64%'");
        return $query->rows;
    }

    public function del_product_status0($product_ids)
    {   
        $delsql               = [];
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_description WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_image WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_option WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_questions WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_special WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_variant WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "ms_product_seller WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        foreach ($delsql as $key => $value) {
            $this->db->query($value);
            //echo($value).'<br>';
        }
    }

    public function get_product_description_list($filter_data)
    {
        $product_id     = isset($filter_data['product_id']) ? $filter_data['product_id'] : 0;
        $sql    = "SELECT * FROM " . DB_PREFIX . "product_description WHERE language_id = 1 AND product_id > '" . $product_id . "'";

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " ORDER BY product_id ASC LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }
        
        $query = $this->db->query($sql);

        return $query->row;
    }

    public function update_product_descriptions($updata = [])
    {
        if (empty($updata))  return;

        $product_ids            = $updata['product_ids'];
        $language_id            = isset($updata['language_id']) ? (int)$updata['language_id'] : 0;

        $setSql                 = '';

        foreach ($updata as $key => $value) {
            if (!in_array($key, ['product_ids','language_id'])) {
                $setSql     .= "`" . $key . "` = '" . $this->db->escape($value) . "',";
            }
        }

        $setSql                = trim($setSql,',');

        if (!empty($setSql)) {
            $sql = "UPDATE `" . DB_PREFIX . "product_description` SET " . $setSql . " WHERE language_id = '" . $language_id . "' AND product_id IN ('" . implode("','",$product_ids) . "')";
            $this->db->query($sql);
        }

    }

    public function update_product_description($updata = [])
    {
        if (empty($updata))  return;

        $product_id             = isset($updata['product_id']) ? (int)$updata['product_id'] : 0;
        $language_id            = isset($updata['language_id']) ? (int)$updata['language_id'] : 0;

        $setSql                 = '';

        foreach ($updata as $key => $value) {
            if (!in_array($key, ['product_id','language_id'])) {
                $setSql     .= "`" . $key . "` = '" . $this->db->escape($value) . "',";
            }
        }

        $setSql                = trim($setSql,',');

        if (!empty($setSql)) {
            $sql = "UPDATE `" . DB_PREFIX . "product_description` SET " . $setSql . " WHERE language_id = '" . $language_id . "' AND product_id = '" . $product_id . "'";
            $this->db->query($sql);
        }

    }

    public function get_product_option_value_total($filter_data)
    {
        $product_id     = isset($filter_data['product_id']) ? (int)$filter_data['product_id'] : 0;
    	$query = $this->db->query("SELECT COUNT(total) AS total FROM (SELECT COUNT(*) as total FROM " . DB_PREFIX . "product_option_value WHERE product_id > " . $product_id . " GROUP BY product_id) AS tt WHERE 1");
      	return $query->row['total'];
    }

    public function get_product_option_value_list($data)
    {
    	$sql 	= "SELECT COUNT(*) as total,product_id FROM " . DB_PREFIX . "product_option_value WHERE 1 GROUP BY product_id ORDER BY product_id";
    	if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

    	$query = $this->db->query($sql);
      	return $query->row;
    }

    public function get_options_description_total($filter_data)
    {
    	$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "option_description WHERE language_id = 2");
      	return $query->row['total'];
    }

    public function get_options_description_list($data)
    {
    	$sql 	= "SELECT option_id,name FROM " . DB_PREFIX . "option_description WHERE language_id = 2";

    	if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

    	$query = $this->db->query($sql);
      	return $query->row;
    }

    public function del_variant_data($variant_id,$variant_value_id)
    {
        $plist      = $this->get_product_variant_list1($variant_id,$variant_value_id);

        $del_pids   = [];
        if (!empty($plist)) {
            foreach ($plist as $key => $value) {
                $del_pids[] = $value['product_id'];
            }
        }

        $delsql               = [];
        if (!empty($del_pids)) {
            $this->del_product_status0($del_pids);
        }

        $delsql               = [];
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "variant_value_description WHERE variant_id = '".(int)$variant_id."' AND variant_value_id = '".(int)$variant_value_id."'";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "variant_value WHERE variant_id = '".(int)$variant_id."' AND variant_value_id = '".(int)$variant_value_id."'";

        foreach ($delsql as $key => $value) {
            $this->db->query($value);
        }
    }

    public function set_variant_value_description($name,$variant_id,$variant_value_id,$language_id)
    {
        $query_sql               = [];
        $query_sql[]             = "DELETE FROM " . DB_PREFIX . "variant_value_description WHERE variant_id = '".(int)$variant_id."' AND variant_value_id = '".(int)$variant_value_id."' AND language_id = '" . (int)$language_id . "'";
        $query_sql[]             = "INSERT INTO `" . DB_PREFIX . "variant_value_description` SET name = '" . $this->db->escape($name) . "', variant_value_id = '" . (int)$variant_value_id . "', variant_id = '" . (int)$variant_id . "', language_id = '" .(int)$language_id. "'";

        foreach ($query_sql as $key => $value) {
            $this->db->query($value);
        }
    }

    public function move_variant_product($olds,$news)
    {
        if (!empty($olds) && !empty($news)) {
            $query_sql      = [];
            $query_sql[]    = "UPDATE `" . DB_PREFIX . "product_variant` SET variant_id = '" . (int)$news['variant_id'] . "',variant_value_id = '" . (int)$news['variant_value_id'] . "' WHERE variant_id = '" . (int)$olds['variant_id'] . "' AND variant_value_id = '" . (int)$olds['variant_value_id'] . "'";

            $query_sql[]    = "DELETE FROM " . DB_PREFIX . "variant_value_description WHERE variant_id = '".(int)$olds['variant_id']."' AND variant_value_id = '".(int)$olds['variant_value_id']."'";
            $query_sql[]    = "DELETE FROM " . DB_PREFIX . "variant_value WHERE variant_id = '".(int)$olds['variant_id']."' AND variant_value_id = '".(int)$olds['variant_value_id']."'";

            foreach ($query_sql as $key => $value) {
                $this->db->query($value);
            }
        }
    }

    public function get_variant_value_description($name)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_value_description WHERE language_id = 2 AND name = '" . $name . "'");
        return $query->rows;
    }

    public function set_variant_value_sort($variant_value_id = 0,$sort_order = 0)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "variant_value` SET sort_order = '" . $sort_order . "' WHERE variant_value_id = '" . (int)$variant_value_id . "'");
    }

    public function get_variant_value_description2($name)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_value_description WHERE language_id = 2 AND name = '" . $name . "'");
        return $query->row;
    }

    public function get_variant_description($name)
    {
    	$query = $this->db->query("SELECT variant_id,name FROM " . DB_PREFIX . "variant_description WHERE language_id = 2 AND name = '" . $name . "'");
    	return $query->row;
    }

    public function get_variant_description_total($language_id = 1)
    {
    	$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "variant_description WHERE language_id = '" . (int)$language_id . "'");
      	return $query->row['total'];
    }

    public function get_variant_description_list($data)
    {
    	$sql 	= "SELECT variant_id,name FROM " . DB_PREFIX . "variant_description WHERE language_id = '" . (int)$data['language_id'] . "'";

    	if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

    	$query = $this->db->query($sql);

      	return $query->row;
    }

    public function update_product($product_id)
    {
    	if ((int)$product_id <= 0)  return;

    	$this->db->query("UPDATE `" . DB_PREFIX . "product` SET sku = '" . $this->get_sku((int)$product_id) . "' WHERE product_id = '" . (int)$product_id . "'");
    }

    public function add_variant($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "variant` SET allow_rename = '0', sort_order = '1'");

        $variant_id = $this->db->getLastId();

        for ($i=1; $i <=2 ; $i++) { 
        	$this->db->query("INSERT INTO " . DB_PREFIX . "variant_description SET variant_id = '" . (int)$variant_id . "', language_id = '" . (int)$i . "', name = '" . $this->db->escape($data['name']) . "'");
        }

        return $variant_id;
    }

    public function get_product_description2()
    {
        $sql = "SELECT p.product_id,p.parent_id,pd.`name`,pd.language_id FROM `oc_product` p LEFT JOIN `oc_product_description` pd ON p.product_id = pd.product_id WHERE pd.`name` LIKE '%925%' AND p.parent_id = 0";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function update_variant_description_name($variant_id = 0,$language_id = 0,$name = '')
    {
    	if ((int)$variant_id <= 0 || $language_id <= 0 || empty($name))  return;

    	$this->db->query("UPDATE `" . DB_PREFIX . "variant_description` SET name = '" . $this->db->escape($name) . "' WHERE variant_id = '" . (int)$variant_id . "' AND language_id = '" . (int)$language_id . "'");
    }

    public function get_option_value_description_list($data)
    {
    	$sql 	= "SELECT option_value_id,option_id,name FROM " . DB_PREFIX . "option_value_description WHERE language_id = 2 AND option_id = '" . $data['option_id'] . "'";
    	$query = $this->db->query($sql);
      	return $query->rows;
    }

    public function get_variant_value_description_list($data)
    {
    	$sql = "SELECT variant_value_id,variant_id,name FROM " . DB_PREFIX . "variant_value_description WHERE language_id = 2 AND variant_id = '" . $data['variant_id'] . "'";
    	$query = $this->db->query($sql);
      	return $query->rows;
    }

    public function add_variant_value_description($data)
    {
        foreach ($data['variant_value'] as $variant_value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value SET variant_id = '" . (int)$data['variant_id'] . "', image = '', sort_order = '1'");

            $variant_value_id = $this->db->getLastId();

            for ($i=1; $i <=2 ; $i++) { 
                $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value_description SET variant_value_id = '" . (int)$variant_value_id . "', language_id = '" . (int)$i . "', variant_id = '" . (int)$data['variant_id'] . "', name = '" . $this->db->escape($variant_value['name']) . "'");
            }
        }
    }

    public function get_variant_value_count()
    {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "variant_value WHERE 1");
        return $query->row['total'];
    }

    public function del_variant_value($variant_id,$variant_value_id)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_value WHERE variant_id = '".(int)$variant_id."' AND variant_value_id = '".(int)$variant_value_id."'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_value_description WHERE variant_id = '".(int)$variant_id."' AND variant_value_id = '".(int)$variant_value_id."'");
    }

    public function get_variant_value_list($data)
    {
        $sql    = "SELECT `variant_value_id`,`variant_id` FROM " . DB_PREFIX . "variant_value WHERE 1";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " ORDER BY variant_id ASC LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function add_variant_option_id($data)
    {
    	if (!empty($data)) {
    		foreach ($data as $key => $value) {
    			$query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "variant_option_id WHERE option_id = '" . (int)$value['option_id'] . "' AND option_value_id = '" . (int)$value['option_value_id'] . "'");
      			if ($query->row['total'] > 0) {
      				unset($data[$key]);
      			}
    		}
    	}

    	if (!empty($data)) {
    		$sql 	= "INSERT INTO " . DB_PREFIX . "variant_option_id (variant_id,variant_value_id,option_id,option_value_id,code) VALUES ";
			foreach ($data as $value) {
				$code 	= (int)$value['option_id'] . '-' . (int)$value['option_value_id'];
				$sql .= "('" . (int)$value['variant_id'] . "','" . (int)$value['variant_value_id'] . "','" . (int)$value['option_id'] . "','" . (int)$value['option_value_id'] . "','" . md5($code) . "'),";
			}

			$sql 		= trim($sql,',');

			$this->db->query($sql);
    	}
    }

    public function get_product($product_id)
    {
    	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "'");
    	return $query->row;
    }

    public function get_product_description($product_id)
    {
    	$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "' ORDER BY language_id ASC");
    	return $query->rows;
    }

    public function add_product_to_category2($product_id,$cats)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
        $this->add_product_to_category($product_id,$cats);
    }

    public function get_product_to_category($product_id)
    {
    	$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");
    	return $query->rows;
    }

    public function get_category_path_level($category_id,$level = 0)
    {
        $query = $this->db->query("SELECT `category_id`,`path_id` FROM " . DB_PREFIX . "category_path WHERE level = '" . (int)$level . "' AND category_id = '" . (int)$category_id . "'");
        return $query->row;
    }

    public function get_category_path($category_ids)
    {
        $query = $this->db->query("SELECT `category_id`,`path_id` FROM " . DB_PREFIX . "category_path WHERE category_id IN ('" . implode("','",$category_ids) . "')");
        return $query->rows;
    }

    private function add_product_to_category($product_id,$category)
    {
        if (!empty($category) && (int)$product_id > 0)
        {
            $insql  = "INSERT INTO " . DB_PREFIX . "product_to_category (product_id,category_id) VALUES ";
            foreach ($category as $value) {
                $insql .= "('" . (int)$product_id . "','" . (int)$value . "'),";
            }

            $insql      = trim($insql,',');
            $this->db->query($insql);
        }
    }

    public function get_sku($product_id)
    {
    	return 'sku' . substr('0000000000000' . $product_id, -13);
    }

    public function get_product_option_value_lists($data)
    {
    	$sql 	= "SELECT option_id,option_value_id FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$data['product_id'] . "'";
    	$query = $this->db->query($sql);
      	return $query->rows;
    }

    public function get_variant_option_id_lists($code)
    {
    	if (empty($code))  return [];

    	$sql 	= "SELECT * FROM " . DB_PREFIX . "variant_option_id WHERE code IN ('" . implode("','",$code) . "')";
    	$query = $this->db->query($sql);
      	return $query->rows;
    }

    public function get_products($product_id)
    {
    	$query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product_id . "' OR parent_id = '" . (int)$product_id . "'");
    	return $query->rows;
    }

    public function add_product($product_count,$product_id,$product,$product_desc,$category,$seo_url = [])
    {
    	if ((int)$product_count <= 1 || (int)$product_id <= 0 || empty($product) || empty($product_desc))  return;

    	$product['parent_id'] 		= $product_id;

        $seller_id          = $product['seller_id'];

        unset($product['seller_id']);
    	for ($i=1; $i < $product_count; $i++)
    	{
    		$insql 		= "INSERT INTO " . DB_PREFIX . "product SET ";

    		foreach ($product as $key => $value)
    		{
    			$insql 	.= $key . " = '" . $this->db->escape($value) . "',";
    		}

    		$insql 		 = trim($insql,',');

    		$this->db->query($insql);

    		$child_pro_id = $this->db->getLastId();

    		$this->update_product($child_pro_id);
    		$this->add_product_to_category($child_pro_id,$category);
            $this->add_ms_product_seller($child_pro_id,$seller_id);
            $this->add_seo_url($child_pro_id,$seo_url);
            $this->add_product_image([['product_id'=>$child_pro_id,'image'=>$product['image']]]);

    		$product_desc[0]['product_id'] 		= $child_pro_id;
            $product_desc[1]['product_id']      = $child_pro_id;

    		for ($j=1; $j <=2 ; $j++)
    		{ 
    			$insql 							= "INSERT INTO " . DB_PREFIX . "product_description SET ";
                $pdesc                          = isset($product_desc[$j-1]) ? $product_desc[$j-1] : [];

    			foreach ($pdesc as $key => $value)
    			{
	    			$insql 	.= $key . " = '" . $this->db->escape($value) . "',";
	    		}

                $insql 	= trim($insql,',');

    			$this->db->query($insql);
            }
    	}
    }

    public function add_ms_product_seller($product_id,$seller_id){
        $this->db->query("DELETE FROM `" . DB_PREFIX . "ms_product_seller` WHERE product_id = '" . (int)$product_id . "'");
        $this->db->query("INSERT INTO `" . DB_PREFIX . "ms_product_seller` SET product_id = '" . (int)$product_id . "', seller_id = '" . (int)$seller_id . "', number_sold = '0', approved = '0'");
    }

    public function add_product_to_store($products)
    {
    	if (!empty($products))
    	{
    		$insql 			= "INSERT INTO " . DB_PREFIX . "product_to_store (product_id,store_id) VALUES ";
    		$product_ids 	= [];

			foreach ($products as $value) {
				$insql 				.= "('" . (int)$value['product_id'] . "','0'),";
				$product_ids[] 		= (int)$value['product_id'];
			}
			
			$product_ids 		= !empty($product_ids) ? $product_ids : [0];
			$delsql 			= "DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id IN ('" . implode("','",$product_ids) . "')";
			$insql 				= trim($insql,',');
			
			$this->db->query($delsql);
			$this->db->query($insql);
    	}
    }

    public function add_product_variant($variant)
    {
    	if (!empty($variant))
    	{
    		$insql 	= "INSERT INTO " . DB_PREFIX . "product_variant (product_id,variant_id,variant_value_id) VALUES ";
			foreach ($variant as $value) {
				$insql .= "('" . (int)$value['product_id'] . "','" . (int)$value['variant_id'] . "','" . (int)$value['variant_value_id'] . "'),";
			}

			$insql 		= trim($insql,',');

			$this->db->query($insql);
    	}
    }

    public function del_product_variant($product_ids)
    {
        $sql    = "DELETE FROM " . DB_PREFIX . "product_variant WHERE product_id IN ('" . implode("','",$product_ids) . "')";
        $this->db->query($sql);
    }

    public function get_product_variant_total1($product_ids)
    {
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "product_variant WHERE product_id IN ('" . implode("','",$product_ids) . "')");
        return $query->row['total'];
    }

    public function get_product_variant_list1($variant_id,$variant_value_id)
    {
        $query = $this->db->query("SELECT `product_id` FROM " . DB_PREFIX . "product_variant WHERE variant_id = '" . (int)$variant_id . "' AND variant_value_id = '" . (int)$variant_value_id . "'");
        return $query->rows;
    }

    public function get_product_variants1($product_ids)
    {
        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_variant WHERE product_id IN ('" . implode("','",$product_ids) . "')");
        return $query->rows;
    }

    public function get_seo_url($product_id)
    {
        $query = $this->db->query("SELECT `store_id`,`language_id`,`query`,`keyword` FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
        return $query->rows;
    }

    private function add_seo_url($product_id,$seo_url = [])
    {
        if ($product_id > 0 && !empty($seo_url)) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product_id . "'");
            $insql  = "INSERT INTO " . DB_PREFIX . "seo_url (store_id,language_id,query,keyword) VALUES ";
            foreach ($seo_url as $key => $value) {
                $url        = explode('=', $value['query']);
                $query      = 'product_id=' . (int)$product_id;
                $keyword    = str_replace('-' . $url[1], '-' . (int)$product_id, $value['keyword']);
                $insql      .= "('" . (int)$value['store_id'] . "','" . (int)$value['language_id'] . "','" . $query . "','" . $keyword . "'),";
            }

            $insql      = trim($insql,',');

            $this->db->query($insql);
        }
    }

    public function clear_table()
    {
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "attribute_description`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "attribute`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "product_attribute`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "option`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "option_description`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "option_value`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "option_value_description`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "product_option`");
    	$this->db->query("TRUNCATE `" . DB_PREFIX . "product_option_value`");
    }

    public function add_image_path($data)
    {
        if (!empty($data)) {
            $sql    = "INSERT INTO " . DB_PREFIX . "image_path (image_path) VALUES ";
            foreach ($data as $value) {
                $sql .= "('" .$value. "'),";
            }

            $sql        = trim($sql,',');
            $this->db->query($sql);
        }
    }

    public function get_product_images_for_image($product_ids)
    {
        $query = $this->db->query("SELECT image FROM " . DB_PREFIX . "product_image WHERE product_id IN ('" .implode("','",$product_ids). "')");
        return $query->rows;
    }

    public function add_image_path2($data)
    {
        if (!empty($data)) {
            $sql    = "INSERT INTO " . DB_PREFIX . "image_path2 (image_path) VALUES ";
            foreach ($data as $value) {
                $sql .= "('" .$value. "'),";
            }

            $sql        = trim($sql,',');
            $this->db->query($sql);
        }
    }

    public function get_product_list_for_image($data)
    {
        $sql    = "SELECT p.product_id,p.image,pd.description FROM oc_product p LEFT JOIN oc_product_description pd ON pd.product_id = p.product_id WHERE p.product_id > 0";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " ORDER BY p.product_id ASC LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function get_all_order_by_customer_id($customer_id = 0)
    {
        $query = $this->db->query("SELECT `order_id` FROM " . DB_PREFIX . "order WHERE customer_id = '" . (int)$customer_id . "'");
        return $query->rows;
    }

    public function del_order_by_order_ids($order_ids)
    {   
        $delsql               = [];
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "order WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "order_history WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "order_product WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "order_total WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "order_variant WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "ms_order_product WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "ms_suborder WHERE order_id IN ('" . implode("','",$order_ids) . "')";
        $delsql[]             = "DELETE FROM " . DB_PREFIX . "ms_suborder_history WHERE order_id IN ('" . implode("','",$order_ids) . "')";

        $query = $this->db->query("SELECT `order_total_id` FROM " . DB_PREFIX . "order_total WHERE order_id IN ('" . implode("','",$order_ids) . "')");
        $totals     = $query->rows;

        $totals_ids = [];

        foreach ($totals as $key => $value) {
            $totals_ids[$value['order_total_id']]   = $value['order_total_id'];
        }

        $totals_ids[0] = 0;

        $delsql[]       = "DELETE FROM " . DB_PREFIX . "ms_order_total WHERE order_total_id IN ('" . implode("','",$totals_ids) . "')";
 
        foreach ($delsql as $key => $value) {
            $this->db->query($value);
        }
    }
}
