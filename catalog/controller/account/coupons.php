<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-11-10 18:12:00
 * @modified         2016-11-10 18:12:00
 */

class ControllerAccountCoupons extends Controller
{
    public function index()
    {
        $data = array();
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/coupons');
            $this->response->redirect($this->url->link('account/login'));
        }

        $this->language->load('account/account');
        $this->language->load('account/coupon');

        $this->document->setTitle($this->language->get('heading_title'));

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
            'text' => $this->language->get('text_coupons'),
            'href' => $this->url->link('account/coupon')
        );
        $data['back'] = $this->url->link('account/account');

        $this->load->model('customercoupon/coupon');
        $results = $this->model_customercoupon_coupon->getCouponsByCustomer($this->customer, false,3);
        $data['coupons'] = array();
        foreach ($results as $result) {
            if ($result['type'] == 2) {
                $discount = '-'.round($result['discount']).'%';
            } else {
                $discount = $this->currency->format($result['discount'], $this->session->data['currency']);
            }
            
            $data['coupons'][] = array(
                'name' => $result['name'],
                'code' => $result['code'],
                'valid' => $result['valid'] && ($result['date_end'] > date('Y-m-d', time())) ? $this->language->get('text_yes') : $this->language->get('text_no'),
                'date_start' => $result['date_start'],
                'valid_icon' => $result['valid'] && ($result['date_end'] > date('Y-m-d', time())) ? '1' : '0',
                'date_end' => $result['date_end'],
                'discount' => $discount,
            );
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }
        $data['error_warning'] = '';

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('account/coupons', $data));
    }
}
