<?php

class ModelCatalogVariant extends Model
{
    public function addVariant($data)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "variant` SET allow_rename = '" . (int)$data['allow_rename'] . "', sort_order = '" . (int)$data['sort_order'] . "'");

        $variant_id = $this->db->getLastId();
        foreach ($data['variant_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "variant_description SET variant_id = '" . (int)$variant_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
        }

        if (isset($data['variant_value'])) {
            foreach ($data['variant_value'] as $variant_value) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value SET variant_id = '" . (int)$variant_id . "', image = '" . $this->db->escape(html_entity_decode($variant_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$variant_value['sort_order'] . "'");

                $variant_value_id = $this->db->getLastId();

                foreach ($variant_value['variant_value_description'] as $language_id => $variant_value_description) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value_description SET variant_value_id = '" . (int)$variant_value_id . "', language_id = '" . (int)$language_id . "', variant_id = '" . (int)$variant_id . "', name = '" . $this->db->escape($variant_value_description['name']) . "'");
                }
            }
        }

        return $variant_id;
    }

    public function editVariant($variant_id, $data)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "variant` SET allow_rename = '" . (int)$data['allow_rename'] . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE variant_id = '" . (int)$variant_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_description WHERE variant_id = '" . (int)$variant_id . "'");

        foreach ($data['variant_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "variant_description SET variant_id = '" . (int)$variant_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
        }

        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_value WHERE variant_id = '" . (int)$variant_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_value_description WHERE variant_id = '" . (int)$variant_id . "'");

        if (isset($data['variant_value'])) {
            foreach ($data['variant_value'] as $variant_value) {
                if ($variant_value['variant_value_id']) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value SET variant_value_id = '" . (int)$variant_value['variant_value_id'] . "', variant_id = '" . (int)$variant_id . "', image = '" . $this->db->escape(html_entity_decode($variant_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$variant_value['sort_order'] . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value SET variant_id = '" . (int)$variant_id . "', image = '" . $this->db->escape(html_entity_decode($variant_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$variant_value['sort_order'] . "'");
                }

                $variant_value_id = $this->db->getLastId();

                foreach ($variant_value['variant_value_description'] as $language_id => $variant_value_description) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "variant_value_description SET variant_value_id = '" . (int)$variant_value_id . "', language_id = '" . (int)$language_id . "', variant_id = '" . (int)$variant_id . "', name = '" . $this->db->escape($variant_value_description['name']) . "'");
                }
            }

        }
    }

    public function deleteVariant($variant_id)
    {
        $variant = \Models\Variant::find($variant_id);
        $variant->variantRelation()->delete();
        $this->db->query("DELETE FROM `" . DB_PREFIX . "variant` WHERE variant_id = '" . (int)$variant_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_description WHERE variant_id = '" . (int)$variant_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_value WHERE variant_id = '" . (int)$variant_id . "'");
        $this->db->query("DELETE FROM " . DB_PREFIX . "variant_value_description WHERE variant_id = '" . (int)$variant_id . "'");
    }

    public function getVariant($variant_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "variant` o LEFT JOIN " . DB_PREFIX . "variant_description od ON (o.variant_id = od.variant_id) WHERE o.variant_id = '" . (int)$variant_id . "' AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getVariants($data = array())
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "variant` o LEFT JOIN " . DB_PREFIX . "variant_description od ON (o.variant_id = od.variant_id) WHERE od.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND od.name LIKE '" . $this->db->escape((string)$data['filter_name']) . "%'";
        }

        $sort_data = array(
            'od.name',
            'o.sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY od.name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

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

        return $query->rows;
    }

    public function getVariantDescriptions($variant_id)
    {
        $variant_data = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_description WHERE variant_id = '" . (int)$variant_id . "'");

        foreach ($query->rows as $result) {
            $variant_data[$result['language_id']] = array('name' => $result['name']);
        }

        return $variant_data;
    }

    public function getVariantValue($variant_value_id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_value ov LEFT JOIN " . DB_PREFIX . "variant_value_description ovd ON (ov.variant_value_id = ovd.variant_value_id) WHERE ov.variant_value_id = '" . (int)$variant_value_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->row;
    }

    public function getVariantValues($variant_id)
    {
        $variant_value_data = array();

        $variant_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_value ov LEFT JOIN " . DB_PREFIX . "variant_value_description ovd ON (ov.variant_value_id = ovd.variant_value_id) WHERE ov.variant_id = '" . (int)$variant_id . "' AND ovd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY ov.sort_order, ovd.name");

        foreach ($variant_value_query->rows as $variant_value) {
            $variant_value_data[] = array(
                'variant_value_id' => $variant_value['variant_value_id'],
                'name' => $variant_value['name'],
                'image' => $variant_value['image'],
                'sort_order' => $variant_value['sort_order']
            );
        }

        return $variant_value_data;
    }

    public function getVariantValueDescriptions($variant_id)
    {
        $variant_value_data = array();

        $variant_value_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_value WHERE variant_id = '" . (int)$variant_id . "' ORDER BY sort_order");

        foreach ($variant_value_query->rows as $variant_value) {
            $variant_value_description_data = array();

            $variant_value_description_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "variant_value_description WHERE variant_value_id = '" . (int)$variant_value['variant_value_id'] . "'");

            foreach ($variant_value_description_query->rows as $variant_value_description) {
                $variant_value_description_data[$variant_value_description['language_id']] = array('name' => $variant_value_description['name']);
            }

            $variant_value_data[] = array(
                'variant_value_id' => $variant_value['variant_value_id'],
                'variant_value_description' => $variant_value_description_data,
                'image' => $variant_value['image'],
                'sort_order' => $variant_value['sort_order']
            );
        }

        return $variant_value_data;
    }

    public function getTotalVariants()
    {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "variant`");

        return $query->row['total'];
    }
}