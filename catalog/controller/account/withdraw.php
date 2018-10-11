<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-16 11:22:04
 * @modified         2016-11-16 11:37:17
 */

class ControllerAccountWithdraw extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('account/withdraw');

        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/withdraw');

            $this->response->redirect($this->url->link('account/login'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('account/withdraw');
            $this->model_account_withdraw->addWithdraw($this->request->post);

            $this->response->redirect($this->url->link('account/withdraw/success'));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home'),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account'),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_withdraw'),
            'href' => $this->url->link('account/withdraw'),
        );

        $data['help_amount'] = sprintf($this->language->get('help_amount'), $this->currency->format($this->config->get('config_withdraw_min'), $this->session->data['currency']), $this->currency->format($this->config->get('config_withdraw_max'), $this->session->data['currency']));

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

        if (isset($this->error['bank_account'])) {
            $data['error_bank_account'] = $this->error['bank_account'];
        } else {
            $data['error_bank_account'] = '';
        }

        $data['action'] = $this->url->link('account/withdraw');

        if (isset($this->request->post['message'])) {
            $data['message'] = $this->request->post['message'];
        } else {
            $data['message'] = '';
        }

        $data['customer_id'] = $this->customer->getId();

        if (isset($this->request->post['amount'])) {
            $data['amount'] = $this->request->post['amount'];
        } else {
            $data['amount'] = $this->currency->format($this->config->get('config_withdraw_min'), $this->config->get('config_currency'), false, false);
        }

        if (isset($this->request->post['bank_account'])) {
            $data['bank_account'] = $this->request->post['bank_account'];
        } else {
            $data['bank_account'] = '';
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

        $this->response->setOutput($this->load->view('account/withdraw', $data));
    }

    protected function validate()
    {
        if ($this->request->post['amount'] <= 0 || $this->request->post['amount'] > $this->customer->getBalance()) {
            $this->error['amount'] = sprintf($this->language->get('error_amount'), $this->customer->getBalance());
        }

        if ($this->request->post['bank_account'] == '') {
            $this->error['bank_account'] = $this->language->get('error_bank_account');
        }

        if (!isset($this->request->post['agree'])) {
            $this->error['warning'] = $this->language->get('error_agree');
        }

        return !$this->error;
    }

    public function success()
    {
        $this->load->language('account/withdraw');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home'),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('account/withdraw'),
        );

        $data['continue'] = $this->url->link('account/withdraw_list');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('common/success', $data));
    }
}
