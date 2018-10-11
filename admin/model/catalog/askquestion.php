<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 11:12:00
 * @modified         2016-11-10 11:12:00
 */

class ModelCatalogAskquestion extends Model
{
    public function GetQuestionProduct($data)
    {
        //var_dump($data);exit();
        $sql = 'SELECT pq.*, pd.name, p.model, p.product_id FROM '.DB_PREFIX.'product_questions pq ';
        $sql .= 'LEFT JOIN '.DB_PREFIX.'product p ON p.product_id=pq.product_id ';
        $sql .= 'LEFT JOIN '.DB_PREFIX.'product_description pd ON pq.product_id=pd.product_id ';
        $sql .= " where pd.language_id = '".(int) $this->config->get('config_language_id')."'";

        if (!empty($data['filter_customer_name'])) {
            $sql .= " AND pq.user_name LIKE '".$this->db->escape($data['filter_customer_name'])."%'";
        }

        if (!empty($data['filter_name'])) {
            $sql .= " AND pd.name LIKE '".$this->db->escape($data['filter_name'])."%'";
        }

        if (!empty($data['filter_model'])) {
            $sql .= " AND p.model LIKE '".$this->db->escape($data['filter_model'])."%'";
        }

        if (!empty($data['filter_question'])) {
            $sql .= " AND pq.product_question LIKE '".$this->db->escape($data['filter_question'])."%'";
        }

        if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
            $sql .= " AND pq.product_status = '".(int) $data['filter_status']."'";
        }

        $sort_data = array(
            'pq.user_name',
            'pd.name',
            'p.model',
            'pq.product_question',
            'pq.product_answer',
            'pq.product_status',
            'pq.question_asked_date',
            'pq.question_answred_date',
            'p.sort_order',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY pq.question_asked_date';
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
                $data['limit'] = 20;
            }

            $sql .= ' LIMIT '.(int) $data['start'].','.(int) $data['limit'];
        }

        $result = $this->db->query($sql);

        return $result->rows;
    }

    public function GetTotalQuestion()
    {
        $totalNoofQuestion = $this->db->query('SELECT COUNT(*) AS totalQuestion FROM '.DB_PREFIX.'product_questions');

        return $totalNoofQuestion->row['totalQuestion'];
    }
}
