<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 14:12:00
 * @modified         2016-11-10 14:12:00
 */

class ModelAccountOreview extends Model
{
    public function addOreview($order_product_id, $data)
    {
        $review = $this->getMasterReviewByOrderProductId($order_product_id);

        if (!$review) {
            $parent_id = 0;
        } else {
            //只能追评一次。
            if ($this->isAdditionalReviewed($review['order_product_review_id'])) {
                return 0;
            }
            $parent_id = $review['order_product_review_id'];
        }
        $query = $this->db->query("SELECT product_id FROM ".DB_PREFIX."order_product WHERE order_product_id = '".(int) $order_product_id."'");
        if (!$query->row) {
            return 0;
        }

        $codes          = array_get($data, 'code');
        $product_id     = $query->row['product_id'];
        $status         = $this->config->get('config_review_approve') ? 0 : 1;
        $is_image       = ($codes && is_array($codes) && count($codes) > 0) ? 1 : 0;

        $this->db->query('INSERT INTO '.DB_PREFIX."order_product_review SET customer_id = '".(int) $this->customer->getId()."', parent_id = '" .  $parent_id .  "', product_id = '".(int) $product_id."', author = '" . $this->customer->getFullName() . "', order_product_id = '".(int) $order_product_id."', text = '".$this->db->escape($data['text'])."', rating = '".(int) $data['rating']."', status = '" . (int)$status . "', is_image = '" . $is_image . "', date_added = NOW()");

        $oreview_id = $this->db->getLastId();

        if ($codes && is_array($codes)) {
            $this->addOreviewImg($oreview_id, $codes);
        }

        return $oreview_id;
    }

    public function addOreviewImg($order_product_review_id, $codes)
    {
        if (count($codes) >=1) {
            $sql = "INSERT INTO `" . DB_PREFIX."order_product_review_image` (`order_product_review_id`, `code`) VALUES ";
            $first = true;
            foreach ($codes as $code) {
                if ($first) {
                    $sql .= "($order_product_review_id, '$code')";
                } else {
                    $sql .= ",($order_product_review_id, '$code')";
                }
                $first = false;
            }

            $this->db->query($sql);
        }
    }

    /**
     * 获取一个商品的所有主评论
     * @param $product_id
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getOreviewsByProductId($product_id, $start = 0, $limit = 20)
    {
        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 20;
        }

        $query = $this->db->query('SELECT r.order_product_review_id, r.customer_id, r.author, r.rating, r.text, p.product_id, pd.name, p.price, p.image, r.date_added
                                   FROM '.DB_PREFIX.'order_product_review r
                                   LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id)
                                   LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id)
                                   WHERE p.product_id = '".(int) $product_id."' AND r.status = '1' AND r.parent_id = 0 AND pd.language_id = '".(int) $this->config->get('config_language_id')."'
                                   ORDER BY r.date_added DESC LIMIT ".(int) $start.','.(int) $limit);

        return $query->rows;
    }

    /**
     * 获取一个商品的所有主评论
     * @param $product_id
     * @return mixed
     */
    public function getTotalOreviewsByProductId($product_id)
    {
        $query = $this->db->query('SELECT COUNT(*) AS total FROM '.DB_PREFIX.'order_product_review r LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id) LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) WHERE r.product_id = '".(int) $product_id."' AND r.status = '1' AND r.parent_id = 0 AND pd.language_id = '".(int) $this->config->get('config_language_id')."'");

        return $query->row['total'];
    }

    public function getOreviewImages($order_product_review_id)
    {
        $query = $this->db->query('SELECT code AS filename
                                   FROM '.DB_PREFIX.'order_product_review_image i
                                   WHERE i.order_product_review_id = '.(int) $order_product_review_id . ';');

        return $query->rows;
    }

    public function isReviewed($order_product_id)
    {
        $query = $this->db->query('SELECT order_product_review_id FROM '.DB_PREFIX."order_product_review r WHERE order_product_id = '".(int) $order_product_id."' AND customer_id = '".(int) $this->customer->getId()."'");

        return $query->num_rows;
    }

    /*
    * 获取当用户购买完成的订单产品列表，只返回主品论，不包含追加评论
    * config_complete_status：订单正常成功完成的状态（不包含取消和关闭的订单）
    */
    public function getOreviews($data = array())
    {
        $order_statuses = $this->config->get('config_complete_status');

        foreach ($order_statuses as $order_status_id) {
            $implode[] = "order_status_id = '".(int) $order_status_id."'";
        }

        $sql = 'SELECT op.*, o.date_added, p.image, p.product_id, opr.author, opr.text, opr.rating, order_product_review_id AS reviewed
                        FROM ' .DB_PREFIX.'order_product op
                        LEFT JOIN ' .DB_PREFIX.'order o ON (o.order_id = op.order_id)
                        LEFT JOIN ' .DB_PREFIX.'product p ON (op.product_id = p.product_id)
                        LEFT JOIN ' .DB_PREFIX.'order_product_review opr ON (opr.order_product_id = op.order_product_id)
                        WHERE (' .implode(' OR ', $implode).') AND (opr.parent_id = 0 OR opr.parent_id IS NULL) ';

        $implode = array();

        if (isset($data['filter_reviewed'])) {
            if ($data['filter_reviewed']) {
                $implode[] = 'opr.order_product_review_id IS NOT NULL';
            } else {
                $implode[] = 'opr.order_product_review_id IS NULL';
            }
        }

        if (isset($data['filter_customer_id'])) {
            $implode[] = "o.customer_id = '".(int) $data['filter_customer_id']."'";
        }

        if (isset($data['filter_order_id'])) {
            $implode[] = "o.order_id = '".(int) $data['filter_order_id']."'";
        }

        if (isset($data['filter_date_added'])) {
            $implode[] = "DATE(o.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        if ($implode) {
            $sql .= ' AND '.implode(' AND ', $implode);
        }

        $sort_data = array(
            'o.order_id',
            'o.date_added',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY o.date_added';
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= ' ASC';
        } else {
            $sql .= ' DESC';
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 10;
            }

            $sql .= ' LIMIT '.(int) $data['start'].','.(int) $data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalOreviews($data = array())
    {
        $order_statuses = $this->config->get('config_complete_status');

        foreach ($order_statuses as $order_status_id) {
            $implode[] = "order_status_id = '".(int) $order_status_id."'";
        }

        $sql = 'SELECT COUNT(*) AS total
                        FROM ' .DB_PREFIX.'order_product op
                        LEFT JOIN ' .DB_PREFIX.'order o ON (o.order_id = op.order_id)
                        LEFT JOIN ' .DB_PREFIX.'order_product_review opr ON (opr.order_product_id = op.order_product_id)
                        WHERE (' .implode(' OR ', $implode).') AND (opr.parent_id = 0 OR opr.parent_id IS NULL) ';

        $implode = array();

        if (isset($data['filter_reviewed'])) {
            if ($data['filter_reviewed']) {
                $implode[] = 'opr.order_product_review_id IS NOT NULL';
            } else {
                $implode[] = 'opr.order_product_review_id IS NULL';
            }
        }

        if (isset($data['filter_reviewed'])) {
            if ($data['filter_reviewed']) {
                $implode[] = 'opr.order_product_review_id IS NOT NULL';
            } else {
                $implode[] = 'opr.order_product_review_id IS NULL';
            }
        }

        if (isset($data['filter_customer_id'])) {
            $implode[] = "o.customer_id = '".(int) $data['filter_customer_id']."'";
        }

        if (isset($data['filter_order_id'])) {
            $implode[] = "o.order_id = '".(int) $data['filter_order_id']."'";
        }

        if (isset($data['filter_date_added'])) {
            $implode[] = "DATE(o.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        if ($implode) {
            $sql .= ' AND '.implode(' AND ', $implode);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getOreviewsForMs($data = array())
    {
        $order_statuses = $this->config->get('config_complete_status');

        foreach ($order_statuses as $order_status_id) {
            $implode[] = "mssu.order_status_id = '".(int) $order_status_id."'";
        }

        $fields         = format_find_field('order_id,date_added,currency_code,currency_value','o');
        $fields         .= ',' . format_find_field('sku,image,name,product_id','op');
        $fields         .= ',' . format_find_field('store_name,seller_id AS msid','ms');
        $fields         .= ',' . format_find_field('order_sn','mssu');

        $sql = 'SELECT DISTINCT op.*, ' . $fields . ' FROM ' . get_tabname('order_product') . ' op
                        LEFT JOIN ' . get_tabname('ms_order_product') .' msop ON (msop.order_product_id = op.order_product_id)
                        LEFT JOIN ' . get_tabname('ms_suborder') .' mssu ON (mssu.order_id = msop.order_id)
                        LEFT JOIN ' . get_tabname('ms_seller') . ' ms ON (ms.seller_id = msop.seller_id)
                        LEFT JOIN ' . get_tabname('order') .' o ON (o.order_id = op.order_id)
                        WHERE (' .implode(' OR ', $implode).') ';

        $implode = array();

        if (isset($data['filter_reviewed'])) {
            if ($data['filter_reviewed']) {
                $implode[] = 'opr.order_product_review_id IS NOT NULL';
            } else {
                $implode[] = 'opr.order_product_review_id IS NULL';
            }
        }

        if (isset($data['filter_customer_id'])) {
            $implode[] = "o.customer_id = '".(int) $data['filter_customer_id']."'";
        }

        if (isset($data['filter_order_id'])) {
            $implode[] = "o.order_id = '".(int) $data['filter_order_id']."'";
        }

        if (isset($data['filter_date_added'])) {
            $implode[] = "DATE(o.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        if ($implode) {
            $sql .= ' AND '.implode(' AND ', $implode);
        }

        $sort_data = array(
            'o.order_id',
            'o.date_added',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY o.date_added';
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= ' ASC';
        } else {
            $sql .= ' DESC';
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 10;
            }

            $sql .= ' LIMIT '.(int) $data['start'].','.(int) $data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getOreview($order_product_review_id)
    {
        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."order_product_review WHERE order_product_review_id = '".(int) $order_product_review_id."'");

        return $query->row;
    }

    public function getRatingByProductId($product_id)
    {
        $query = $this->db->query('SELECT AVG(rating) AS rating FROM '.DB_PREFIX.'order_product_review r LEFT JOIN '.DB_PREFIX."order_product op ON (r.order_product_id = op.order_product_id) WHERE op.product_id = '".(int) $product_id."' AND r.status = '1' ");

        return $query->row['rating'];
    }

    public function getOreviewsByOrderProductId($order_product_id) {
        $query = $this->db->query('SELECT r.order_product_review_id, r.customer_id, r.parent_id, r.author, r.rating, r.text, r.status, p.product_id, pd.name, p.price, p.image, r.date_added
                                   FROM '.DB_PREFIX.'order_product_review r
                                   LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id)
                                   LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id)
                                   WHERE r.order_product_id = '".(int) $order_product_id."' AND pd.language_id = '".(int) $this->config->get('config_language_id')."'");

        return $query->rows;
    }

    /**
     * 包括启用和禁用的评论
     * @param $order_product_id
     * @return mixed
     */
    public function getMasterReviewByOrderProductId($order_product_id) {
        $query = $this->db->query('SELECT r.order_product_review_id, r.customer_id, r.author, r.rating, r.text, p.product_id, pd.name, p.price, p.image, r.date_added
                                   FROM '.DB_PREFIX.'order_product_review r
                                   LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id)
                                   LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id)
                                   WHERE r.order_product_id = '".(int) $order_product_id."' AND r.parent_id = 0 AND pd.language_id = '".(int) $this->config->get('config_language_id')."'");

        return $query->row;
    }

    /**
     * 只获取启用的评论
     * @param $order_product_review_id
     * @return mixed
     */
    public function getAdditionalReviews($order_product_review_id) {
        $query = $this->db->query('SELECT r.order_product_review_id, r.customer_id, r.author, r.rating, r.text, p.product_id, pd.name, p.price, p.image, r.date_added
                                   FROM '.DB_PREFIX.'order_product_review r
                                   LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id)
                                   LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id)
                                   WHERE r.parent_id = '".(int) $order_product_review_id."' AND r.status = '1' AND pd.language_id = '".(int) $this->config->get('config_language_id')."'");

        return $query->rows;
    }

    public function isAdditionalReviewed($order_product_review_id) {
        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."order_product_review WHERE parent_id = '".(int) $order_product_review_id."'");

        return $query->rows;
    }

    public function getReviewReply($review_id) {
        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."review_reply WHERE order_product_review_id = '".(int) $review_id."'");

        return $query->row;
    }


    /**
     * 获取一个商品的所有主评论-支持子产品
     * @param $product_id
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getOreviewsByProductIds($product_id, $start = 0, $limit = 20,$dtype = 0,$rating = 0)
    {
        if (empty($product_id))  return [];

        if ($start < 0) {
            $start = 0;
        }

        if ($limit < 1) {
            $limit = 20;
        }

        $maps          = '';
        if ($dtype == 1) {
            $maps      .= "AND r.is_image = '1' ";//有图
        }else if ($dtype == 2) {
            $maps      .= "AND r.is_image = '0' ";//无图
        }

        if (in_array((int)$rating, [1,2,3,4,5])) {
            $maps      .= "AND r.rating = '" . (int)$rating . "' ";//无图
        }

        $query = $this->db->query('SELECT r.order_product_review_id, r.order_product_id,r.customer_id, r.author, r.rating,op.sku, r.text, p.product_id, pd.name, p.price, p.image, r.date_added
                                   FROM '.DB_PREFIX.'order_product_review r
                                   LEFT JOIN '.DB_PREFIX.'order_product op ON (op.order_product_id = r.order_product_id)
                                   LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id)
                                   LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id)
                                   WHERE p.product_id IN (" . implode(',', $product_id) . ") AND r.status = '1' " . $maps . "AND r.parent_id = 0 AND pd.language_id = '".(int) $this->config->get('config_language_id')."'
                                   ORDER BY r.date_added DESC LIMIT ".(int)$start * $limit .','.(int) $limit);

        return $query->rows;
    }

    /**
     * 获取一个商品的所有主评论 - 支持子产品
     * @param $product_id
     * @return mixed
     */
    public function getTotalOreviewsByProductIds($product_id,$dtype = 0,$rating = 0)
    {
        if (empty($product_id))  return [];

        $maps          = '';
        if ($dtype == 1) {
            $maps      .= "AND r.is_image = '1' ";//有图
        }else if ($dtype == 2) {
            $maps      .= "AND r.is_image = '0' ";//无图
        }

        if (in_array((int)$rating, [1,2,3,4,5])) {
            $maps      .= "AND r.rating = '" . (int)$rating . "' ";//无图
        }

        $query = $this->db->query('SELECT COUNT(*) AS total FROM '.DB_PREFIX.'order_product_review r LEFT JOIN '.DB_PREFIX.'product p ON (r.product_id = p.product_id) LEFT JOIN '.DB_PREFIX."product_description pd ON (p.product_id = pd.product_id) WHERE r.product_id IN (" . implode(',', $product_id) . ") AND r.status = '1' AND r.parent_id = 0 " . $maps . "AND pd.language_id = '".(int) $this->config->get('config_language_id')."'");

        return $query->row['total'];
    }

    public function getOreviewsTotalForApi($data = [])
    {
        $order_statuses = $this->config->get('config_complete_status');

        foreach ($order_statuses as $order_status_id) {
            $implode[] = "mssu.order_status_id = '".(int) $order_status_id."'";
        }

        $sql = 'SELECT COUNT(*) AS total
                        FROM ' .DB_PREFIX.'order_product op
                        LEFT JOIN ' .DB_PREFIX.'order o ON (o.order_id = op.order_id)
                        LEFT JOIN ' .DB_PREFIX.'ms_suborder mssu ON (o.order_id = mssu.order_id)
                        LEFT JOIN ' .DB_PREFIX.'product p ON (op.product_id = p.product_id)
                        LEFT JOIN ' .DB_PREFIX.'order_product_review opr ON (opr.order_product_id = op.order_product_id)
                        WHERE (' .implode(' OR ', $implode).') AND (opr.parent_id = 0 OR opr.parent_id IS NULL) ';

        $implode = array();

        if (isset($data['filter_reviewed'])) {
            if ($data['filter_reviewed']) {
                $implode[] = 'opr.order_product_review_id IS NOT NULL';
            } else {
                $implode[] = 'opr.order_product_review_id IS NULL';
            }
        }

        if (isset($data['filter_customer_id'])) {
            $implode[] = "opr.customer_id = '".(int) $data['filter_customer_id']."'";
        }

        if (isset($data['filter_date_added'])) {
            $implode[] = "DATE(o.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        if ($implode) {
            $sql .= ' AND '.implode(' AND ', $implode);
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /*
    * 获取当用户购买完成的订单产品列表，只返回主品论，不包含追加评论
    * config_complete_status：订单正常成功完成的状态（不包含取消和关闭的订单）
    */
    public function getOreviewsForApi($data = [])
    {
        $order_statuses = $this->config->get('config_complete_status');

        foreach ($order_statuses as $order_status_id) {
            $implode[] = "mssu.order_status_id = '".(int) $order_status_id."'";
        }

        $sql = 'SELECT op.*, o.date_added, p.image, p.product_id,p.tax_class_id, opr.author, opr.text, opr.rating, order_product_review_id AS reviewed
                        FROM ' .DB_PREFIX.'order_product op
                        LEFT JOIN ' .DB_PREFIX.'order o ON (o.order_id = op.order_id)
                        LEFT JOIN ' .DB_PREFIX.'ms_suborder mssu ON (o.order_id = mssu.order_id)
                        LEFT JOIN ' .DB_PREFIX.'product p ON (op.product_id = p.product_id)
                        LEFT JOIN ' .DB_PREFIX.'order_product_review opr ON (opr.order_product_id = op.order_product_id)
                        WHERE (' .implode(' OR ', $implode).') AND (opr.parent_id = 0 OR opr.parent_id IS NULL) ';

        $implode = array();

        if (isset($data['filter_reviewed'])) {
            if ($data['filter_reviewed']) {
                $implode[] = 'opr.order_product_review_id IS NOT NULL';
            } else {
                $implode[] = 'opr.order_product_review_id IS NULL';
            }
        }

        if (isset($data['filter_customer_id'])) {
            $implode[] = "opr.customer_id = '".(int) $data['filter_customer_id']."'";
        }

        if (isset($data['filter_order_id'])) {
            $implode[] = "o.order_id = '".(int) $data['filter_order_id']."'";
        }

        if (isset($data['filter_date_added'])) {
            $implode[] = "DATE(o.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        if ($implode) {
            $sql .= ' AND '.implode(' AND ', $implode);
        }

        $sort_data = array(
            'o.order_id',
            'o.date_added',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY o.date_added';
        }

        if (isset($data['order']) && ($data['order'] == 'ASC')) {
            $sql .= ' ASC';
        } else {
            $sql .= ' DESC';
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 10;
            }

            $sql .= ' LIMIT '.(int) $data['start'].','.(int) $data['limit'];
        }
        
        $query = $this->db->query($sql);

        return $query->rows;
    }
}
