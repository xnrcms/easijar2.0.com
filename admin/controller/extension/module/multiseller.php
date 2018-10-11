<?php
class ControllerExtensionModuleMultiseller extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/multiseller');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_multiseller', $this->request->post);

            $this->load->model('setting/modification');

            $modification_info = $this->model_setting_modification->getModificationByCode('Multiseller_module');
            if ($modification_info) {
                if ($this->request->post['module_multiseller_status']) {
                    $this->model_setting_modification->enableModification($modification_info['modification_id']);
                } else {
                    $this->model_setting_modification->disableModification($modification_info['modification_id']);
                }
            }

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'));
		}

        $data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/multiseller', 'user_token=' . $this->session->data['user_token'])
		);

		$data['action'] = $this->url->link('extension/module/multiseller', 'user_token=' . $this->session->data['user_token']);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$this->load->model('multiseller/seller_group');

		$data['seller_groups'] = $this->model_multiseller_seller_group->getSellerGroups();

		if (isset($this->request->post['module_multiseller_seller_group'])) {
			$data['module_multiseller_seller_group'] = $this->request->post['module_multiseller_seller_group'];
		} else {
			$data['module_multiseller_seller_group'] = $this->config->get('module_multiseller_seller_group');
		}

		if (isset($this->request->post['module_multiseller_product_validation'])) {
			$data['module_multiseller_product_validation'] = $this->request->post['module_multiseller_product_validation'];
		} else {
			$data['module_multiseller_product_validation'] = $this->config->get('module_multiseller_product_validation');
		}

		if (isset($this->request->post['module_multiseller_seller_approval'])) {
			$data['module_multiseller_seller_approval'] = $this->request->post['module_multiseller_seller_approval'];
		} else {
			$data['module_multiseller_seller_approval'] = $this->config->get('module_multiseller_seller_approval');
		}

		if (isset($this->request->post['module_multiseller_seller_id'])) {
			$data['module_multiseller_seller_id'] = $this->request->post['module_multiseller_seller_id'];
		} else {
			$data['module_multiseller_seller_id'] = $this->config->get('module_multiseller_seller_id');
		}

        if (isset($this->request->post['module_multiseller_seller_shipping'])) {
            $data['module_multiseller_seller_shipping'] = $this->request->post['module_multiseller_seller_shipping'];
        } else {
            $data['module_multiseller_seller_shipping'] = $this->config->get('module_multiseller_seller_shipping');
        }

        if (isset($this->request->post['module_multiseller_seller_categories'])) {
            $categories = $this->request->post['module_multiseller_seller_categories'];
        } else {
            $categories = $this->config->get('module_multiseller_seller_categories');
        }

        $data['module_multiseller_seller_categories'] = array();

        $this->load->model('catalog/category');

        foreach ($categories as $category_id) {
            $category_info = $this->model_catalog_category->getCategory($category_id);

            if ($category_info) {
                $data['module_multiseller_seller_categories'][] = array(
                    'category_id' => $category_info['category_id'],
                    'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }

		$this->load->model('catalog/information');

		$data['informations'] = $this->model_catalog_information->getInformations();

		if (isset($this->request->post['module_multiseller_status'])) {
			$data['module_multiseller_status'] = $this->request->post['module_multiseller_status'];
		} else {
			$data['module_multiseller_status'] = $this->config->get('module_multiseller_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/multiseller', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/multiseller')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
        $this->load->model('extension/module/multiseller');
        $this->model_extension_module_multiseller->install();

		// Register the event triggers
		$this->load->model('setting/event');

		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/common/header/before', 'seller/event/viewCommonHeaderBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'admin/view/common/column_left/before', 'multiseller/event/viewCommonColumnLeftBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'admin/controller/customer/customer_approval/approve/before', 'multiseller/event/controllerCustomerCustomerApprovalApproveBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'admin/view/sale/order_info/before', 'multiseller/event/viewSaleOrderInfoBefore');
        $this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/checkout/cart/before', 'seller/event/viewCheckoutCartBefore');
        $this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/checkout/cart/_product_list/before', 'seller/event/viewCheckoutCartBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/checkout/checkout/_confirm/before', 'seller/event/viewCheckoutCheckoutConfirmBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/model/checkout/order/addOrder/after', 'seller/event/modelCheckoutOrderAddOrderAfter');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/model/checkout/order/addOrderHistory/before', 'seller/event/modelCheckoutOrderAddOrderHistoryBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/product/product/before', 'seller/event/viewProductProductBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/account/order_info/before', 'seller/event/viewAccountOrderInfoBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/view/account/return_form/before', 'seller/event/viewAccountReturnFormBefore');
		$this->model_setting_event->addEvent('cn.opencart.multiseller', 'catalog/model/account/return/addReturn/after', 'seller/event/modelAccountReturnAddReturnAfter');

		$this->load->model('setting/modification');

        $modification_info = $this->model_setting_modification->getModificationByCode('Multiseller_module');
        if ($modification_info) {
            $this->model_setting_modification->disableModification($modification_info['modification_id']);
        }
    }

	public function uninstall() {
        $this->load->model('extension/module/multiseller');
        $this->model_extension_module_multiseller->uninstall();

		// Register the event triggers
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('cn.opencart.multiseller');

		$this->load->model('setting/modification');

        $modification_info = $this->model_setting_modification->getModificationByCode('Multiseller_module');
        if ($modification_info) {
            $this->model_setting_modification->disableModification($modification_info['modification_id']);
        }
    }
}