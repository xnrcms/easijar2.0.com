<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-16 11:22:04
 * @modified         2016-11-16 11:37:17
 */

class ControllerAccountWithdrawList extends Controller
{
    public function index()
    {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/withdraw_list', '', true);

            $this->response->redirect($this->url->link('account/login', '', true));
        }

        $this->load->language('account/withdraw');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home'),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_account'),
            'href' => $this->url->link('account/account', '', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_withdraw'),
            'href' => $this->url->link('account/withdraw', '', true),
        );

        $this->load->model('account/withdraw');

        $data['column_amount'] = sprintf($this->language->get('column_amount'), $this->config->get('config_currency'));

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['withdraws'] = array();

        $filter_data = array(
            'sort' => 'date_added',
            'order' => 'DESC',
            'start' => ($page - 1) * 10,
            'limit' => 10,
        );

        $withdraw_total = $this->model_account_withdraw->getTotalWithdraws();

        $results = $this->model_account_withdraw->getWithdraws($filter_data);

        foreach ($results as $result) {
            $data['withdraws'][] = array(
                'amount' => $this->currency->format($result['amount'], $this->config->get('config_currency')),
                'message' => $result['message'],
                'bank_account' => $result['bank_account'],
                'status' => $result['status'],
                'refused' => $result['refused'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
            );
        }

        $pagination = new Pagination();
        $pagination->total = $withdraw_total;
        $pagination->page = $page;
        $pagination->limit = 10;
        $pagination->url = $this->url->link('account/withdraw', 'page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($withdraw_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($withdraw_total - 10)) ? $withdraw_total : ((($page - 1) * 10) + 10), $withdraw_total, ceil($withdraw_total / 10));

        $data['total'] = $this->currency->format($this->customer->getBalance(), $this->session->data['currency']);

        $data['continue'] = $this->url->link('account/account', '', true);
        $data['recharge'] = $this->url->link('account/withdraw', '', true);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/withdraw_list', $data));
    }
}
