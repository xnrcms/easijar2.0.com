<?php
class ControllerExtensionModuleSellerAccount extends Controller {
	public function index() {
		$this->load->language('extension/module/seller_account');

		$data['logged'] = $this->customer->isLogged();
		$data['register'] = $this->url->link('seller/register');
		$data['login'] = $this->url->link('seller/login');
		$data['logout'] = $this->url->link('account/logout');
		$data['forgotten'] = $this->url->link('account/forgotten');
        $data['dashboard'] = $this->url->link('seller/account');
        $data['edit'] = $this->url->link('seller/edit');
        $data['password'] = $this->url->link('account/password');
        $data['transaction'] = $this->url->link('seller/transaction');
        $data['withdraw'] = $this->url->link('seller/withdraw');
        $data['product'] = $this->url->link('seller/product');
        $data['order'] = $this->url->link('seller/order');
        $data['shipping'] = $this->url->link('seller/shipping_cost');

		return $this->load->view('extension/module/seller_account', $data);
	}
}