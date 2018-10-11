<?php
class ModelNotifyNotify extends Model {
    /**
     * @param $telephone
     * @param $verify_code
     * 该函数只用于发送短信，参数需要传入手机号码
     */
    public function customerRegisterVerify($telephone, $verify_code) {
        $this->load->model('notify/sms');

        $params = array(
            'code'        => $verify_code
        );

        return $this->model_notify_sms->send($telephone, $params, $this->config->get('module_sms_customer_register_verify_message')[(int)$this->config->get('config_language_id')]);
    }

    /**
     * @param $verify_code
     * 该函数只用于发送短信，参数需要传入手机号码
     */
    public function findBackPassword($telephone, $verify_code) {
        $this->load->model('notify/sms');

        $params = array(
            'code'         => $verify_code
        );

        return $this->model_notify_sms->send($telephone, $params, $this->config->get('module_sms_find_back_password')[(int)$this->config->get('config_language_id')]);
    }

    public function customerAddTransaction($customer_name, $balance, $description) {
        $this->load->model('notify/sms');

        $params = array(
            'customer'      => $customer_name,
            'balance'       => $balance,
            'total'         => $this->currency->format($this->customer->getBalance(), $this->config->get('config_currency')),
            'description'   => $description,
            'date'          => date($this->language->get('datetime_format'), time())
        );

        return $this->model_notify_sms->send($this->customer->getTelephone(), $params, $this->config->get('module_sms_customer_add_transaction_message')[(int)$this->config->get('config_language_id')]);
    }

    public function customerRegisterLogin($customer_info) {
        $this->load->model('notify/sms');

        $params = array(
            'date'          => date($this->language->get('datetime_format'), time())
        );

        if ($customer_info['telephone']) {
            return $this->model_notify_sms->send($customer_info['telephone'], $params, $this->config->get('module_sms_customer_register_login_message')[(int)$this->config->get('config_language_id')]);
        }
    }

    public function customerRegisterApproval($customer_info) {
        $this->load->model('notify/sms');

        $params = array(
            'date'          => date($this->language->get('datetime_format'), time())
        );

        if ($customer_info['telephone']) {
            return $this->model_notify_sms->send($customer_info['telephone'], $params, $this->config->get('module_sms_customer_register_approval_message')[(int)$this->config->get('config_language_id')]);
        }
    }

    /* 根customerAddTransaction重复，所以该方法取消，customerAddTransaction中增加了$description参数，可以描述余额的来源为充值 */
    public function transactionRecharge($recharge_id, $amount) {
        $this->load->model('notify/sms');

        $params = array(
            'recharge_id'   => $recharge_id,
            'amount'        => $amount,
            'date'          => date($this->language->get('datetime_format'), time())
        );

        return $this->model_notify_sms->send($this->customer->getTelephone(), $params, $this->config->get('module_sms_transaction_recharge_message')[(int)$this->config->get('config_language_id')]);
    }

    public function orderEffect($order_id, $order_status) {
        $this->load->model('notify/sms');
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);
        $params = array(
            'order_id'     => $order_id,
            'status'       => $order_status,
            'date'         => date($this->language->get('datetime_format'), time())
        );

        return $this->model_notify_sms->send($order_info['telephone'], $params, $this->config->get('module_sms_order_effect_message')[(int)$this->config->get('config_language_id')]);
    }

    public function orderUpdate($order_id, $order_status) {
        $this->load->model('notify/sms');

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);
        $params = array(
            'order_id'     => $order_id,
            'status'       => $order_status,
            'date'         => date($this->language->get('datetime_format'), time())
        );

        return $this->model_notify_sms->send($order_info['telephone'], $params, $this->config->get('module_sms_order_update_message')[(int)$this->config->get('config_language_id')]);
    }

    public function orderPaidAlert($order_id, $order_total, $customer_name) {
        $this->load->model('notify/sms');

        $params = array(
            'order_id'     => $order_id,
            'amount'       => $order_total,
            'customer'     => $customer_name,
            'date'         => date($this->language->get('datetime_format'), time())
        );

        return $this->model_notify_sms->send($this->config->get('config_telephone'), $params, $this->config->get('module_sms_order_paid_notify_admin_message')[(int)$this->config->get('config_language_id')]);
    }
}