<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-16 11:22:04
 * @modified         2016-11-16 11:37:17
 */

class ModelAccountWithdraw extends Model
{
    public function addWithdraw($data)
    {
        $this->db->query('INSERT INTO '.DB_PREFIX."withdraw SET customer_id = '".(int) $this->customer->getId()."', amount = '".(float) $data['amount']."', bank_account = '".trim($data['bank_account'])."', status = 0, refused = 0, message = '".$this->db->escape($data['message'])."', date_added =  NOW()");

        $withdraw_id = $this->db->getLastId();

        // Add transaction
        $this->load->language('account/withdraw');
        $this->load->model('account/customer');
        $this->model_account_customer->addTransaction($this->customer->getId(), $this->language->get('text_withdraw_trans').$withdraw_id, -(float) $data['amount'], 0, 0, $withdraw_id);

        return $withdraw_id;
    }

    public function getWithdraws($data = array())
    {
        $sql = 'SELECT * FROM `'.DB_PREFIX."withdraw` WHERE customer_id = '".(int) $this->customer->getId()."'";

        $sort_data = array(
            'amount',
            'message',
            'date_added',
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= ' ORDER BY '.$data['sort'];
        } else {
            $sql .= ' ORDER BY date_added';
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

    public function getTotalWithdraws()
    {
        $query = $this->db->query('SELECT COUNT(*) AS total FROM `'.DB_PREFIX."withdraw` WHERE customer_id = '".(int) $this->customer->getId()."'");

        return $query->row['total'];
    }
}
