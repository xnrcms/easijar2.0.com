<?php

class ModelCatalogOreview extends Model
{
    public function addReview($data)
    {
        $customer_id = isset($data['customer_id']) ? $data['customer_id'] : 0;
        $date_added = $data['date_added'] ? $this->db->escape((string)$data['date_added']) : 'NOW()';
        $this->db->query('INSERT INTO '.DB_PREFIX."order_product_review SET product_id = '".(int) $data['product_id']."', customer_id = '".(int) $customer_id."', author = '".$this->db->escape(strip_tags($data['author']))."', text = '".$this->db->escape(strip_tags($data['text']))."', rating = '".(int) $data['rating']."', status = '".(int) $data['status']."', date_added = '".$date_added."'");

        $order_product_review_id = $this->db->getLastId();

        $codes = array_get($data, 'images');
        if ($codes && is_array($codes)) {
            $this->deleteReviewImage($order_product_review_id);
            $this->addOreviewImg($order_product_review_id, $codes);
        }

        $this->cache->delete('product');

        return $order_product_review_id;
    }

    public function editReview($order_product_review_id, $data)
    {
        $customer_id = isset($data['customer_id']) ? $data['customer_id'] : 0;
        $date_added = $data['date_added'] ? $this->db->escape((string)$data['date_added']) : 'NOW()';
        $this->db->query('UPDATE '.DB_PREFIX."order_product_review SET product_id = '".(int) $data['product_id']."', customer_id = '".(int) $customer_id."', author = '".$this->db->escape(strip_tags($data['author']))."', text = '".$this->db->escape(strip_tags($data['text']))."', rating = '".(int) $data['rating']."', status = '".(int) $data['status']."', date_added = '".$date_added."', date_modified = NOW() WHERE order_product_review_id = '".(int) $order_product_review_id."'");

        $codes = array_get($data, 'images');
        if ($codes && is_array($codes)) {
            $this->deleteReviewImage($order_product_review_id);
            $this->addOreviewImg($order_product_review_id, $codes);
        }

        $this->cache->delete('product');
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

    public function deleteReviewImage($order_product_review_id)
    {
        $this->db->query('DELETE FROM '.DB_PREFIX."order_product_review_image WHERE order_product_review_id = '".(int) $order_product_review_id."'");

        $this->cache->delete('product');
    }

    public function deleteReview($order_product_review_id)
    {
        $this->db->query('DELETE FROM '.DB_PREFIX."order_product_review WHERE order_product_review_id = '".(int) $order_product_review_id."' OR parent_id = '".(int) $order_product_review_id."'");
        $this->db->query('DELETE FROM '.DB_PREFIX."review_reply WHERE order_product_review_id = '".(int) $order_product_review_id."'");

        $this->cache->delete('product');
    }

    public function getReview($order_product_review_id)
    {
        $query = $this->db->query('SELECT DISTINCT *, pd.name AS product FROM '.DB_PREFIX.'order_product_review r LEFT JOIN '.DB_PREFIX."product_description pd ON (r.product_id = pd.product_id AND pd.language_id = " . (int)$this->config->get('config_language_id') . ") WHERE r.order_product_review_id = '".(int) $order_product_review_id."'");

        return $query->row;
    }

    public function getReviewImage($order_product_review_id)
    {
        $query = $this->db->query('SELECT DISTINCT code AS image FROM '.DB_PREFIX."order_product_review_image r 
                                   WHERE r.order_product_review_id = '".(int) $order_product_review_id."'");

        return $query->rows;
    }

    public function getReviews($data = array())
    {
        $sql = 'SELECT r.order_product_review_id, pd.name, c.fullname AS customer, r.customer_id, r.author, r.rating, r.status, r.date_added 
            FROM ' .DB_PREFIX.'order_product_review r 
            LEFT JOIN ' .DB_PREFIX.'product_description pd ON (r.product_id = pd.product_id AND pd.language_id = ' . (int)$this->config->get('config_language_id') . ') 
            LEFT JOIN ' .DB_PREFIX.'customer c ON (c.customer_id = r.customer_id) 
            WHERE 1=1';

        if (!empty($data['filter_product'])) {
            $sql .= " AND p.name LIKE '".$this->db->escape($data['filter_product'])."%'";
        }

        if (!empty($data['filter_customer'])) {
            $sql .= " AND c.fullname LIKE '".$this->db->escape($data['filter_customer'])."%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND r.status = '".(int) $data['filter_status']."'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(r.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        $sort_data = array(
            'p.name',
            'customer',
            'r.rating',
            'r.status',
            'r.date_added',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY r.date_added';
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= ' DESC';
        } else {
            $sql .= ' ASC';
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= ' LIMIT '.(int) $data['start'].','.(int) $data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalReviews($data = array())
    {
        $sql = 'SELECT COUNT(*) AS total 
            FROM ' .DB_PREFIX.'order_product_review r 
            LEFT JOIN ' .DB_PREFIX.'product p ON (r.product_id = p.product_id) 
            LEFT JOIN ' .DB_PREFIX.'customer c ON (c.customer_id = r.customer_id) 
            WHERE 1=1';

        if (!empty($data['filter_product'])) {
            $sql .= " AND p.name LIKE '".$this->db->escape($data['filter_product'])."%'";
        }

        if (!empty($data['filter_customer'])) {
            $sql .= " AND c.fullname LIKE '".$this->db->escape($data['filter_customer'])."%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND r.status = '".(int) $data['filter_status']."'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(r.date_added) = DATE('".$this->db->escape($data['filter_date_added'])."')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getTotalReviewsAwaitingApproval()
    {
        $query = $this->db->query('SELECT COUNT(*) AS total FROM '.DB_PREFIX."order_product_review WHERE status = '0'");

        return $query->row['total'];
    }

    public function import($file) {
        $this->load->language('catalog/review');

        $file = fopen($file,"r");
        $first = true;

        $this->load->model('catalog/product');
        while(!feof($file)) {
            $review = fgetcsv($file); // 上传文件按格式： 商品ID， 评论者名字， 评价日期， 评价内容， 评分， 晒图

            if (!$review) {
                return true;
            }
            $review = $this->utf8($review);

            if ($first) {
                $first = false;
                $template = explode(',', $this->language->get('text_csv_template'));

                if ($review[0] != $template[0] || $review[1] != $template[1] || $review[2] != $template[2] || $review[3] != $template[3]) {
                    return sprintf($this->language->get('error_csv_template'), $this->language->get('text_csv_template'));
                }
                continue;
            }

            if (!$this->model_catalog_product->getProduct($review[0])) {
                return sprintf($this->language->get('error_product_id'), $review[0]);
            }

            $data = array(
                'status'     => 1,
                'product_id' => $review[0],
                'author'     => $review[1],
                'date_added' => $review[2],
                'text'       => $review[3],
                'rating'     => $review[4],
                'images'     => !trim($review[5]) ? array() : array_map(function ($item){return trim($item);}, explode(';',$review[5]))
            );

            $this->addReview($data);
        }

        fclose($file);

        return true;
    }

    private function utf8($data) {
        if (is_array($data)) {
            foreach ($data as $key=>$item) {
                $encode = mb_detect_encoding($item, array("GB2312","GBK","BIG5","ASCII","UTF-8"));
                if( $encode != "UTF-8") {
                    $data[$key] = mb_convert_encoding($item, 'UTF-8', $encode);
                }
            }

        } else {
            $encode = mb_detect_encoding($data, array("GB2312","GBK","BIG5","ASCII","UTF-8"));
            if( $encode != "UTF-8") {
                $data = mb_convert_encoding($data, 'UTF-8', $encode);
            }
        }

        return $data;
    }

    public function addReviewReply($review_id, $data) {
        $this->db->query('INSERT INTO '.DB_PREFIX."review_reply SET order_product_review_id = '".(int) $review_id."', content = '".$this->db->escape(strip_tags($data['content']))."', user_id = '" . $this->user->getId() . "', date_added = NOW(), date_modified = NOW()");

        $reply_id = $this->db->getLastId();

        return $reply_id;
    }

    public function getReviewReply($review_id) {
        $query = $this->db->query('SELECT * FROM '.DB_PREFIX."review_reply WHERE order_product_review_id = '".(int) $review_id."'");

        return $query->row;
    }
}
