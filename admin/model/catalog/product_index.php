<?php
/**
 * product_index.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-04-16 14:42
 * @modified   2018-04-16 14:42
 */


class ModelCatalogProductIndex extends Model
{
    public function addIndex($data)
    {
        $sql = "INSERT INTO " . DB_PREFIX . "product_index SET product_id = '" . (int)$data['product_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', rating = '" . (float)$data['rating'] . "', sales = '" . (int)$data['sale'] . "', discount = '" . (float)$data['discount'] . "', special = '" . (float)$data['special'] . "'";
        $this->db->query($sql);

        $indexId = $this->db->getLastId();
        return $indexId;
    }

    public function clearIndex()
    {
        $sql = "TRUNCATE TABLE " . DB_PREFIX . "product_index";
        $this->db->query($sql);
    }

    public function getRatings()
    {
        $sql = "SELECT p.product_id, AVG(rating) AS total FROM " . DB_PREFIX . "product as p INNER JOIN " . DB_PREFIX . "review as r on p.product_id = r.product_id WHERE r. STATUS = '1' GROUP BY p.product_id";
        $query = $this->db->query($sql);

        $result = [];
        if (empty($query->rows)) {
            return $result;
        }
        $indexProducts = $this->registry->get('index_products', []);
        foreach ($query->rows as $row) {
            $indexProducts[] = $row['product_id'];
            $result[$row['product_id']] = $row['total'];
        }
        $this->registry->set('index_products', $indexProducts);
        return $result;
    }

    public function getSales()
    {
        $sql = "SELECT p.product_id, SUM(op.quantity) AS total FROM " . DB_PREFIX . "product as p INNER JOIN " . DB_PREFIX . "order_product as op on p.product_id = op.product_id GROUP BY p.product_id";
        $query = $this->db->query($sql);

        $result = [];
        if (empty($query->rows)) {
            return $result;
        }
        $indexProducts = $this->registry->get('index_products', []);
        foreach ($query->rows as $row) {
            $indexProducts[] = $row['product_id'];
            $result[$row['product_id']] = $row['total'];
        }
        $this->registry->set('index_products', $indexProducts);
        return $result;
    }

    public function getDiscounts()
    {
        $sql = "SELECT p.product_id, pd.price as price FROM " . DB_PREFIX . "product as p INNER JOIN " . DB_PREFIX . "product_discount as pd on p.product_id = pd.product_id WHERE pd.product_id = p.product_id AND pd.quantity = '1' AND ((pd.date_start = '0000-00-00' OR pd.date_start < NOW()) AND (pd.date_end = '0000-00-00' OR pd.date_end > NOW())) ORDER BY pd.priority ASC, pd.price ASC";

        $query = $this->db->query($sql);
        $result = [];
        if (empty($query->rows)) {
            return $result;
        }
        $indexProducts = $this->registry->get('index_products', []);
        foreach ($query->rows as $row) {
            if (isset($result[$row['product_id'] . '-' . $row['customer_group_id']])) {
                continue;
            }
            $indexProducts[] = $row['product_id'];
            $result[$row['product_id'] . '-' . $row['customer_group_id']] = $row['price'];
        }
        $this->registry->set('index_products', $indexProducts);
        return $result;
    }

    public function getSpecials()
    {
        $sql = "SELECT p.product_id, ps.customer_group_id, ps.price FROM " . DB_PREFIX . "product as p INNER JOIN " . DB_PREFIX . "product_special as ps on p.product_id = ps.product_id WHERE ps.product_id = p.product_id AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC";
        $query = $this->db->query($sql);

        $result = [];
        if (empty($query->rows)) {
            return $result;
        }
        $indexProducts = $this->registry->get('index_products', []);
        foreach ($query->rows as $row) {
            if (isset($result[$row['product_id'] . '-' . $row['customer_group_id']])) {
                continue;
            }
            $indexProducts[] = $row['product_id'];
            $result[$row['product_id'] . '-' . $row['customer_group_id']] = $row['price'];
        }
        $this->registry->set('index_products', $indexProducts);
        return $result;
    }
}