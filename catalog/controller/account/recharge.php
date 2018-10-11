<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-14 10:00:00
 * @modified         2016-11-14 10:00:00
 */

class ControllerAccountRecharge extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('account/recharge');

        $this->document->setTitle($this->language->get('heading_title'));

        if (!isset($this->session->data['recharges'])) {
            $this->session->data['recharges'] = array();
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->session->data['recharges'][1] = array(
                'description' => $this->language->get('text_for'),
                'customer_id' => $this->request->post['customer_id'],
                'message' => $this->request->post['message'],
                'amount' => $this->currency->convert($this->request->post['amount'], $this->session->data['currency'], $this->config->get('config_currency')),
            );

            $this->cart->select(array());
            $this->response->redirect($this->url->link('checkout/checkout'));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_recharge'),
            'href' => $this->url->link('account/recharge')
        );

        $data['help_amount'] = sprintf($this->language->get('help_amount'), $this->currency->format($this->config->get('config_recharge_min'), $this->session->data['currency']), $this->currency->format($this->config->get('config_recharge_max'), $this->session->data['currency']));

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['amount'])) {
            $data['error_amount'] = $this->error['amount'];
        } else {
            $data['error_amount'] = '';
        }

        $data['action'] = $this->url->link('account/recharge');

        if (isset($this->request->post['message'])) {
            $data['message'] = $this->request->post['message'];
        } else {
            $data['message'] = '';
        }

        $data['customer_id'] = $this->customer->getId();

        if (isset($this->request->post['amount'])) {
            $data['amount'] = $this->request->post['amount'];
        } else {
            $data['amount'] = $this->currency->format($this->config->get('config_recharge_min'), $this->session->data['currency'], false, false);
        }

        if (isset($this->request->post['agree'])) {
            $data['agree'] = $this->request->post['agree'];
        } else {
            $data['agree'] = false;
        }

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/recharge', $data));
    }

    protected function validate()
    {
        if ($this->currency->convert($this->request->post['amount'], $this->session->data['currency'], $this->config->get('config_currency')) < 1) {
            $this->error['amount'] = sprintf($this->language->get('error_amount'), $this->currency->format($this->config->get('config_recharge_min'), $this->session->data['currency']), $this->currency->format($this->config->get('config_recharge_max'), $this->session->data['currency']));
        }

        if (!isset($this->request->post['agree'])) {
            $this->error['warning'] = $this->language->get('error_agree');
        }

        return !$this->error;
    }

    public function success()
    {
        $this->load->language('account/recharge');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home'),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/recharge'),
        );

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('common/success', $data));
    }
}
