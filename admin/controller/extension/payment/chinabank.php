<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2017-07-14 11:12:00
 * @modified         2017-07-14 15:11:10
 */

class ControllerExtensionPaymentChinabank extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/chinabank');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_chinabank', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment'));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['id'])) {
			$data['error_id'] = $this->error['id'];
		} else {
			$data['error_id'] = '';
		}
		
		if (isset($this->error['key'])) {
			$data['error_key'] = $this->error['key'];
		} else {
			$data['error_key'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment')
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/chinabank', 'user_token=' . $this->session->data['user_token'])
		);

		$data['action'] = $this->url->link('extension/payment/chinabank', 'user_token=' . $this->session->data['user_token']);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=payment');

		if (isset($this->request->post['payment_chinabank_id'])) {
			$data['payment_chinabank_id'] = $this->request->post['payment_chinabank_id'];
		} else {
			$data['payment_chinabank_id'] = $this->config->get('payment_chinabank_id');
		}

		if (isset($this->request->post['payment_chinabank_key'])) {
			$data['payment_chinabank_key'] = $this->request->post['payment_chinabank_key'];
		} else {
			$data['payment_chinabank_key'] = $this->config->get('payment_chinabank_key');
		}

		$data['callback'] = $this->url->link('checkout/success', 'user_token=' . $this->session->data['user_token']);

		if (isset($this->request->post['payment_chinabank_total'])) {
			$data['payment_chinabank_total'] = $this->request->post['payment_chinabank_total'];
		} else {
			$data['payment_chinabank_total'] = $this->config->get('payment_chinabank_total');
		} 

		if (isset($this->request->post['payment_chinabank_completed_status_id'])) {
			$data['payment_chinabank_completed_status_id'] = $this->request->post['payment_chinabank_completed_status_id'];
		} else {
			$data['payment_chinabank_completed_status_id'] = $this->config->get('payment_chinabank_completed_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_chinabank_geo_zone_id'])) {
			$data['payment_chinabank_geo_zone_id'] = $this->request->post['payment_chinabank_geo_zone_id'];
		} else {
			$data['payment_chinabank_geo_zone_id'] = $this->config->get('payment_chinabank_geo_zone_id');
		}

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		if (isset($this->request->post['payment_chinabank_status'])) {
			$data['payment_chinabank_status'] = $this->request->post['payment_chinabank_status'];
		} else {
			$data['payment_chinabank_status'] = $this->config->get('payment_chinabank_status');
		}

		if (isset($this->request->post['payment_chinabank_sort_order'])) {
			$data['payment_chinabank_sort_order'] = $this->request->post['payment_chinabank_sort_order'];
		} else {
			$data['payment_chinabank_sort_order'] = $this->config->get('payment_chinabank_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/payment/chinabank', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/chinabank')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_chinabank_id']) {
			$this->error['id'] = $this->language->get('error_id');
		}

		if (!$this->request->post['payment_chinabank_key']) {
			$this->error['key'] = $this->language->get('error_key');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
}
