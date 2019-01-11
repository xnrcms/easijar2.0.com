<?php
class ModelCatalogHandle2 extends Model {
    public function get_product_description2($data)
    {
        $sql    = "SELECT pd.product_id,pd.description pd_desc,pdc.description pdc_desc FROM `oc_product_description` pd LEFT JOIN `oc_product_description_copy_copy` pdc ON (pd.language_id = pdc.language_id AND pd.product_id = pdc.product_id) WHERE pd.product_id <= 6618 AND pd.language_id = 1";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " ORDER BY pd.product_id ASC LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function update_desc($description,$product_id)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "product_description` SET description = '" . $this->db->escape($description) . "' WHERE product_id = '" . (int)$product_id . "'");
    }
}
