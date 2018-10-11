<?php
class ControllerExtensionModuleAccount extends Controller {
	public function index() {
		$this->load->language('extension/module/account');

		$data['logged'] = $this->customer->isLogged();
		$data['register'] = $this->url->link('account/register');
		$data['login'] = $this->url->link('account/login');
		$data['logout'] = $this->url->link('account/logout');
		$data['forgotten'] = $this->url->link('account/forgotten');
		$data['account'] = $this->url->link('account/account');
		$data['edit'] = $this->url->link('account/edit');
		$data['password'] = $this->url->link('account/password');
		$data['address'] = $this->url->link('account/address');
		$data['wishlist'] = $this->url->link('account/wishlist');
		$data['order'] = $this->url->link('account/order');
        $this->load->language('account/oreview', 'review');
        $data['text_oreview'] = $this->language->get('review')->get('text_oreview');
        $data['oreview'] = $this->url->link('account/oreview');
		$data['download'] = $this->url->link('account/download');
		$data['reward'] = $this->url->link('account/reward');
		$data['return'] = $this->url->link('account/return');
        $this->load->language('account/withdraw', 'withdraw');
        $data['text_withdraw'] = $this->language->get('withdraw')->get('text_withdraw');
        $data['withdraw_list'] = $this->url->link('account/withdraw_list');
		$data['transaction'] = $this->url->link('account/transaction');
		$data['newsletter'] = $this->url->link('account/newsletter');
        $this->load->language('account/coupon', 'coupon');
        $data['text_coupons'] = $this->language->get('coupon')->get('text_coupons');
        $data['coupons'] = $this->url->link('account/coupons');

		return $this->load->view('extension/module/account', $data);
	}
}