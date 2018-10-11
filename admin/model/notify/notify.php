<?php
class ModelNotifyNotify extends Model {
    public function customerApprove($customer_id, $customer_name) {
        $this->load->model('notify/sms');
        $this->load->model('customer/customer');

        $customer_info = $this->model_customer_customer->getCustomer($customer_id);

        if ($customer_info) {
            $params = array(
                'customer' => $customer_name,
                'customer_id' => $customer_id,
                'date' => date($this->language->get('datetime_format'), time())
            );

            return $this->model_notify_sms->send($customer_info['telephone'], $params, $this->config->get('module_sms_customer_approve_message')[(int)$this->config->get('config_language_id')]);
        }
    }

    public function returnUpdateMessage($customer_id, $return_id, $return_status)
    {
        $this->load->model('notify/sms');
        $this->load->model('customer/customer');

        $customer_info = $this->model_customer_customer->getCustomer($customer_id);

        if ($customer_info) {
            $params = array(
                'return_id' => $return_id,
                'status' => $return_status,
                'date' => date($this->language->get('datetime_format'), time())
            );

            return $this->model_notify_sms->send($customer_info['telephone'], $params, $this->config->get('module_sms_return_update_message_message')[(int)$this->config->get('config_language_id')]);
        }
    }

    public function customerAddReward($customer_id, $reward) {
        $this->load->model('notify/sms');
        $this->load->model('customer/customer');

        $customer_info = $this->model_customer_customer->getCustomer($customer_id);

        if ($customer_info) {
            $params = array(
                'customer' => $customer_info['fullname'],
                'reward' => $reward,
                'date' => date($this->language->get('datetime_format'), time())
            );

            return $this->model_notify_sms->send($customer_info['telephone'], $params, $this->config->get('module_sms_customer_add_reward_message')[(int)$this->config->get('config_language_id')]);
        }
    }

    public function customerAddTransaction($customer_id, $balance, $total, $description) {
        $this->load->model('notify/sms');
        $this->load->model('customer/customer');

        $customer_info = $this->model_customer_customer->getCustomer($customer_id);

        if ($customer_info) {
            $params = array(
                'customer'      => $customer_info['fullname'],
                'balance'       => $balance,
                'total'         => $total,
                'description'   => $description,
                'date'          => date($this->language->get('datetime_format'), time())
            );

            return $this->model_notify_sms->send($customer_info['telephone'], $params, $this->config->get('module_sms_customer_add_transaction_message')[(int)$this->config->get('config_language_id')]);
        }
    }
}