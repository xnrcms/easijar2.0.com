<?php
/**
 * Quick Checkout
 *
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Sam Chen <sam.chen@opencart.cn>
 * @created          2017-07-31 11:48:04
 * @modified         2017-08-14 19:14:47
 */

class ControllerCheckoutCheckout extends Controller
{
    private $ADDRESS_FIELDS = array(
        'fullname',
        'telephone',
        'company',
        'address_1',
        'address_2',
        'city',
        'postcode',
        'country_id',
        'zone_id',
        'city_id',
        'county_id',
        'custom_field',
    );

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('checkout/cart');
        $this->load->language('checkout/coupon');
        $this->load->language('checkout/checkout');
        $this->load->model('account/activity');
        $this->load->model('account/custom_field');
        $this->load->model('tool/upload');
        $this->load->model('account/address');
        $this->load->model('account/customer');
        $this->load->model('account/customer_group');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->model('checkout/checkout');
        $this->load->model('checkout/order');

        if ($this->isLogged()) {
            unset($this->session->data['guest']);
        }
    }

    public function index()
    {
        if (!$this->isValidCart()) {
            $this->log('Cart invalid');
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        if (!$this->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('checkout/cart');
            $this->session->data['error'] = t('warning_login');
            $this->response->redirect($this->url->link('account/login'));
            return;
        }

        // Shipping address
        $this->initAddressSession('shipping');

        // Payment address
        $this->initAddressSession('payment');

        // Init pickup
        if ($this->hasShipping()) {
            unset($this->session->data['pickup_id']);
        }

        if ($this->hasShipping()) {
            $this->log($this->session->data['shipping_address']);
        }
        $this->log($this->session->data['payment_address']);

        $this->load->language('checkout/checkout');
        $this->document->setTitle(t('heading_title'));
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        // Required by klarna
        if ($this->config->get('klarna_account') || $this->config->get('klarna_invoice')) {
            $this->document->addScript('http://cdn.klarna.com/public/kitt/toc/v1.0/js/klarna.terms.min.js');
        }

        $breadcrumbs = new Breadcrumb();
        $breadcrumbs->add(t('text_home'), $this->url->link('common/home'));
        $breadcrumbs->add(t('text_cart'), $this->url->link('checkout/cart'));
        $breadcrumbs->add(t('heading_title'), $this->url->link('checkout/checkout'));
        $data['breadcrumbs'] = $breadcrumbs->all();

        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $data['logged'] = $this->isLogged();
        $data['shipping_required'] = $this->hasShipping();
        $data['payment_address_required'] = $this->isPaymentAddressRequired();

        if ($this->hasShipping()) {
            $data['shipping_address_section'] = $this->renderAddressSection('shipping');
        }

        if ($this->isPaymentAddressRequired()) {
            $data['payment_address_section'] = $this->renderAddressSection('payment');
        }

        $data['if_pickup_section'] = $this->renderIfPickupSection();
        $data['pickup_section'] = $this->renderPickupSection();
        $data['shipping_method_section'] = $this->renderShippingMethodSection();
        $data['payment_method_section'] = $this->renderPaymentMethodSection();

        $data['cart_section'] = $this->renderCartSection();
        //$data['comment_section'] = $this->renderCommentSection();
        $data['agree_section'] = $this->renderAgreeSection();

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('checkout/checkout/checkout', $data));
    }

    // Update checkout
    public function update()
    {
        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->log(__FUNCTION__);
        $this->log($this->request->post);

        $redirect = '';
        $error = array();

        if (!$this->isLogged()) {
            $redirect = $this->url->link('account/login');
            $this->printJson($error, $redirect);
            return;
        }

        if (!$this->isValidCart()) {
            $redirect = $this->url->link('checkout/cart');
            $this->printJson($error, $redirect);
            return;
        }

        // Shipping address id
        if ($addressId = array_get($this->request->post, 'shipping_address_id')) {
            if (!$this->hasShipping()) {
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['shipping_method']);
            } else {
                $address = $this->model_account_address->getAddress($addressId);
                $this->syncAddressSession('shipping', $address);

                if (! $this->isPaymentAddressRequired()) {
                    $this->syncAddressSession('payment', $address);
                }

                $code = array_get($this->session->data, 'shipping_method.code');
                if (!$this->model_checkout_checkout->setShippingMethod($code)) {
                    $this->model_checkout_checkout->setShippingMethod();
                }

                if (! $this->isPaymentAddressRequired()) {
                    $code = array_get($this->session->data, 'payment_method.code');
                    if (!$this->model_checkout_checkout->setPaymentMethod($code)) {
                        $this->model_checkout_checkout->setPaymentMethod();
                    }
                }
            }

            $this->printJson($error, $redirect);
            return;
        }

        // Payment address id
        if ($addressId = array_get($this->request->post, 'payment_address_id')) {
            $address = $this->model_account_address->getAddress($addressId);
            $this->syncAddressSession('payment', $address);

            $code = array_get($this->session->data, 'payment_method.code');
            if (!$this->model_checkout_checkout->setPaymentMethod($code)) {
                $this->model_checkout_checkout->setPaymentMethod();
            }

            $this->printJson($error, $redirect);
            return;
        }

        // Payment method
        if ($code = array_get($this->request->post, 'payment_method')) {
            if (!array_get($this->session->data, 'payment_address')) {
                $redirect = $this->url->link('checkout/cart');
                $this->printJson($error, $redirect);
                return;
            }

            if (!$this->model_checkout_checkout->setPaymentMethod($code)) {
                $this->session->data['error'] = t('error_payment_unavailable');
                $redirect = $this->url->link('checkout/checkout');
            }

            $this->printJson($error, $redirect);
            return;
        }

        // Shipping method
        if ($code = array_get($this->request->post, 'shipping_method')) {
            if (!array_get($this->session->data, 'shipping_address')) {
                $redirect = $this->url->link('checkout/cart');
                $this->printJson($error, $redirect);
                return;
            }

            if (!$this->model_checkout_checkout->setShippingMethod($code)) {
                $this->session->data['error'] = t('error_shipping_unavailable');
                $redirect = $this->url->link('checkout/checkout');
            }

            $this->printJson($error, $redirect);
            return;
        }

        // IF Pickup
        if (isset($this->request->post['is_pickup'])) {
            $this->session->data['is_pickup'] = (bool)$this->request->post['is_pickup'];
            if (!$this->session->data['is_pickup'] && !isset($this->session->data['shipping_address'])) {
                $this->initAddressSession('shipping');
            }
            $this->printJson($error, $redirect);
            return;
        }

        // Pickup
        if (isset($this->request->post['pickup_id'])) {
            $this->session->data['pickup_id'] = $this->request->post['pickup_id'];
            $this->printJson($error, $redirect);
            return;
        }

        // Comment
        if (isset($this->request->post['comment'])) {
            $this->session->data['comment'] = $this->request->post['comment'];
            $this->printJson($error, $redirect);
            return;
        }

        // Agreement
        if (isset($this->request->post['terms'])) {
            $this->session->data['checkout_terms'] = $this->request->post['terms'];
            $this->printJson($error, $redirect);
            return;
        }
    }

    // Validate and submit order
    public function confirm()
    {
        $this->log(__FUNCTION__);
        $redirect = '';
        $error = array();

        if ($this->request->server['REQUEST_METHOD'] != 'POST') {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        if (!$this->isLogged()) {
            $redirect = $this->url->link('account/login');
            $this->printJson($error, $redirect);
            return;
        }

        if (!$this->isValidCart()) {
            $redirect = $this->url->link('checkout/cart');
            $this->printJson($error, $redirect);
            return;
        }

        $this->log($this->request->post);

        $order_data = array();

        $order_data['payment_address'] = array();
        $order_data['shipping_address'] = array();

        // Shipping address
        if ($this->hasShipping()) {
            $addressId = (int)array_get($this->request->post, 'shipping_address_id');
            if (! $addressId) {
                $error['shipping_address'] = t('error_address');
                $this->printJson($error, $redirect);
                return;
            }

            $address = $this->model_account_address->getAddress($addressId);
            if (!$address) { // Selected address not exists anymore
                $this->session->data['warning_error'] = t('error_address_not_exist');
                $redirect = $this->url->link('checkout/checkout');
                $this->printJson($error, $redirect);
                return;
            }

            $order_data['shipping_address'] = $address;
            $this->syncAddressSession('shipping', $address);

            if (! $this->isPaymentAddressRequired()) {
                $order_data['payment_address'] = $address;
                $this->syncAddressSession('payment', $address);
            }
        } else {
            // None shipping required cart just need a dummy payment address
            unset($this->session->data['shipping_address']);

            if (! $this->isPaymentAddressRequired()) {
                $this->fakeGuestAddressSession('payment');
                $order_data['payment_address'] = $this->session->data['payment_address'];
            }

            if ($this->config->get('config_checkout_pickup') && !array_get($this->session->data, 'pickup_id', 0)) {
                $error['pickup'] = t('error_pickup');
            }
        }

        // Payment address
        if ($this->isPaymentAddressRequired()) {
            $addressId = (int)array_get($this->request->post, 'payment_address_id');
            if (! $addressId) {
                $error['payment_address'] = t('error_address');
                $this->printJson($error, $redirect);
                return;
            }

            $address = $this->model_account_address->getAddress($addressId);
            if (!$address) { // Selected address not exists anymore
                $this->session->data['warning_error'] = t('error_address_not_exist');
                $redirect = $this->url->link('checkout/checkout');
                $this->printJson($error, $redirect);
                return;
            }

            $order_data['payment_address'] = $address;
            $this->syncAddressSession('payment', $address);
        }

        // Payment method
        if (!array_get($this->request->post, 'payment_method')) {
            $error['payment_method']['warning'] = t('error_payment');
        } else {
            $code = array_get($this->request->post, 'payment_method');
            if (!$this->model_checkout_checkout->setPaymentMethod($code)) {
                $error['payment_method']['warning'] = t('error_payment_unavailable');
            } else {
                $order_data['payment_method'] = $code;
            }
        }

        // Shipping method
        if ($this->hasShipping()) {
            if (empty($error['shipping_address'])) {
                if (!array_get($this->request->post, 'shipping_method')) {
                    $error['shipping_method']['warning'] = t('error_shipping');
                } else {
                    $code = array_get($this->request->post, 'shipping_method');
                    if (!$this->model_checkout_checkout->setShippingMethod($code)) {
                        $error['shipping_method']['warning'] = t('error_shipping_unavailable');
                    } else {
                        $shipping = explode('.', $code);
                        $order_data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                    }
                }
            }
        } else {
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['shipping_method']);
        }

        // Commentcomment_
        $comment            = [];
        foreach ($this->request->post as $key => $value) {
            if (strpos($key, 'comment_') === 0) {
                $comment[trim($key,'comment_')]     = $value;
            }
        }

        $order_data['comment'] = $comment;

        // Terms & conditions agreement
        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');
            $information = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
            if ($information && !array_get($this->request->post, 'terms')) {
                $error['agree']['terms'] = sprintf(t('error_agree'), $information['title']);
            }
        }

        if ($error) {
            $this->printJson($error, $redirect);
            return;
        }

        // ALL set, update address session then submit the order
        $this->session->data['payment_address'] = $order_data['payment_address'];
        if ($this->hasShipping()) {
            $this->session->data['shipping_address'] = $order_data['shipping_address'];
        } else {
            unset($this->session->data['shipping_address']);
        }

        try {
            // Comment
            $this->session->data['comment'] = json_encode($order_data['comment']);

            $order_id = $this->model_checkout_checkout->createOrder();
            $this->cart->clear();

            // Change order status to Unpaid
            if ($order_data['payment_method'] != 'cod') {
                $this->model_checkout_order->addOrderHistory($order_id, config('config_unpaid_status_id'));
            } else { // cod order does not need unpaid status
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_cod_order_status_id'));
            }

            $this->printJson($error, $redirect);
            return;
        } catch (\Exception $e) {
            $error['checkout'] = $e->getMessage();
            $this->printJson($error, $redirect);
            return;
        }
    }

    public function reload()
    {
        //设置购物车类型
        $this->cart->setCartBuyType((isset($this->session->data['buy_type']) ? $this->session->data['buy_type'] : 0));
        
        if ($this->hasShipping()) {
            $data['shipping_address_section'] = $this->renderAddressSection('shipping');
        }

        if ($this->isPaymentAddressRequired()) {
            $data['payment_address_section'] = $this->renderAddressSection('payment');
        }

        $data['if_pickup_section'] = $this->renderIfPickupSection();
        $data['pickup_section'] = $this->renderPickupSection();
        $data['payment_method_section'] = $this->renderPaymentMethodSection();
        $data['shipping_method_section'] = $this->renderShippingMethodSection();
        $data['cart_section'] = $this->renderCartSection();
        //$data['comment_section'] = $this->renderCommentSection();
        $data['agree_section'] = $this->renderAgreeSection();
        $this->response->setOutput($this->load->view('checkout/checkout/_main_section', $data));
    }

    // Address form
    public function address_form()
    {
        if (!$this->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('checkout/cart');
            $this->session->data['error'] = t('warning_login');
            $this->response->redirect($this->url->link('account/login'));
        }

        if ($address_id = array_get($this->request->get, 'address_id')) {
            $address = $this->model_account_address->getAddress($address_id);
            if (!$address) {
                $address_id = 0;
            } else {
                $data['fullname'] = $address['fullname'];
                $data['telephone'] = $address['telephone'];
                $data['company'] = $address['company'];
                $data['address_1'] = $address['address_1'];
                $data['address_2'] = $address['address_2'];
                $data['postcode'] = $address['postcode'];
                $data['city'] = $address['city'];
                $data['zone_id'] = $address['zone_id'];
                $data['zone'] = $address['zone'];
                $data['zone_code'] = $address['zone_code'];
                $data['country_id'] = $address['country_id'];
                $data['country'] = $address['country'];
                $data['city_id'] = $address['city_id'];
                $data['county_id'] = $address['county_id'];
                $data['address_custom_field'] = $address['custom_field'];
                $data['default'] = $this->customer->getAddressId() == $address['address_id'];
            }
        }

        if (!$address_id) {
            $data['country_id'] = array_get($this->session->data, 'shipping_address.country_id', config('config_country_id'));
            $data['zone_id'] = array_get($this->session->data, 'shipping_address.zone_id', config('config_zone_id'));
            $data['postcode'] = array_get($this->session->data, 'shipping_address.postcode');
        }

        $this->load->model('localisation/country');
        $data['countries'] = $this->model_localisation_country->getCountries();

        // Custom Fields
        $this->load->model('account/custom_field');
        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
        $data['custom_fields'] = [];
        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] == 'address') {
                $data['custom_fields'][] = $custom_field;
            }
        }

        $data['address_id'] = $address_id;
        $data['type'] = array_get($this->request->get, 'type', 'shipping');

        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('checkout/checkout/_address_form', $data));
    }

    public function save_address()
    {
        $redirect = '';
        $error = [];

        if (!$this->isLogged()) {
            $redirect = $this->url->link('account/login');
            $this->printJson($error, $redirect);
            return;
        }

        if (($this->request->server['REQUEST_METHOD'] != 'POST')) {
            $redirect = $this->url->link('checkout/cart');
            $this->printJson($error, $redirect);
            return;
        }

        if ($error = $this->validateAddress()) {
            $this->printJson($error, $redirect);
            return;
        }

        $addressId = (int)array_get($this->request->get, 'address_id');
        if ($addressId > 0) {
            $this->model_account_address->editAddress($addressId, $this->request->post);
        } else {
            $addressId = $this->model_account_address->addAddress($this->customer->getId(), $this->request->post);
        }

        $address = $this->model_account_address->getAddress($addressId);
        $type = array_get($this->request->get, 'type', 'shipping');
        $this->syncAddressSession($type, $address);

        if ($type == 'shipping' && empty($this->session->data["payment_address"]['address_id'])) {
            $this->syncAddressSession('payment', $address);
        }

        $this->printJson($error, $redirect);
    }

    // Payment connect page when order created
    public function connect()
    {
        $this->log(__FUNCTION__);
        if (!isset($this->session->data['order_id']) || (int)$this->session->data['order_id'] <= 0) {
            $this->response->redirect($this->url->link('common/home'));
        }

        $data['order_id'] = $order_id = (int)$this->session->data['order_id'];
        $order = $this->model_checkout_order->getOrder($order_id);
        if (!$order) {
            $this->response->redirect($this->url->link('common/home'));
        }

        // Redirect cod order to checkout/success page
        if ($order['payment_code'] == 'cod') {
            $this->response->redirect($this->url->link('checkout/success'));
        }

        // Check if order is unpaid
        if ($order['order_status_id'] != config('config_unpaid_status_id')) {
            $this->response->redirect($this->url->link('account/order/info', 'order_id=' . $order['order_id']));
        }

        $this->load->language('checkout/connect');

        $this->document->setTitle(t('heading_title'));
        $data['heading_title'] = t('heading_title');

        $data['text_success'] = t('text_success');
        $data['column_order_id'] = t('column_order_id');
        $data['column_total'] = t('column_total');
        $data['column_shipping_method'] = t('column_shipping_method');
        $data['column_payment_method'] = t('column_payment_method');
        $data['button_view'] = t('button_view');

        $data['total'] = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']);

        $data['shipping_method'] = $order['shipping_method'] ?: false;
        $data['payment_method'] = $order['payment_method'];

        $payment_code = $order['payment_code'];
        if ($payment_code == 'cod') {
            $data['payment_view'] = false;
        } else {
            $data['payment_view'] = $this->load->controller("extension/payment/{$payment_code}");
        }

        $data['href'] = $this->url->link('account/order/info', 'order_id=' . $order['order_id']);

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('checkout/connect', $data));
    }

    // Helpers
    private function isLogged()
    {
        return $this->customer->isLogged();
    }

    private function hasShipping()
    {
        return $this->cart->hasShipping();
    }

    private function isValidCart()
    {
        //设置购物车类型
        $this->cart->setCartBuyType((isset($this->session->data['buy_type']) ? $this->session->data['buy_type'] : 0));

        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers']) && empty($this->session->data['recharges'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            return false;
        }

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();

        foreach ($products as $product) {
            $product_total = 0;

            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                return false;
            }
        }

        return true;
    }

    private function printJson($error = array(), $redirect = '')
    {
        $json = array(
            'error' => $error ? (object)$error : null,
            'redirect' => $redirect
        );
        $this->jsonOutput($json);
    }

    // Is customer required to set payment address
    private function isPaymentAddressRequired()
    {
        // return is_ft();
        return false;
    }

    // Views
    private function renderAddressSection($type = 'shipping')
    {
        $this->log(__FUNCTION__);
        $data['logged'] = $this->isLogged();

        $address_id = (int)array_get($this->session->data, "{$type}_address.address_id");
        if ($address_id) {
            if(! $this->model_account_address->getAddress($address_id)) {
                $address_id = 0;
            }
        }

        $data['address_id'] = $address_id ?: $this->customer->getAddressId();
        $data['addresses'] = $this->model_account_address->getAddresses();

        foreach ($data['addresses'] as $addressId => $address) {
            if ($addressId == $data['address_id']) {
                $defaultAddress = $address;
                unset($data['addresses'][$addressId]);
                array_unshift($data['addresses'], $defaultAddress);
                break;
            }
        }

        // Don't show new address for payment address section when not address
        if ($type == 'payment' && $this->isPaymentAddressRequired() && $this->hasShipping() && !$data['addresses']) {
            return;
        }

        return $this->load->view("checkout/checkout/_{$type}_address", $data);
    }

    private function renderPaymentMethodSection()
    {
        $this->log(__FUNCTION__);
        if (isset($this->session->data['payment_address'])) {
            $this->model_checkout_checkout->getPaymentMethods();
        }

        if (empty($this->session->data['payment_methods'])) {
            $data['error_warning'] = sprintf(t('error_no_payment'), $this->url->link('information/contact'));
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['payment_methods'])) {
            $data['payment_methods'] = $this->session->data['payment_methods'];
        } else {
            $data['payment_methods'] = array();
        }

        if (isset($this->session->data['payment_method']['code'])) {
            $data['code'] = $this->session->data['payment_method']['code'];
        } else {
            $data['code'] = '';
        }

        if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }

        return $this->load->view('checkout/checkout/_payment_method', $data);
    }

    private function renderIfPickupSection() {
        $this->log(__FUNCTION__);

        // 自提功能关闭时，或商品本身不需要配送时返回，商品需要配送时不管顾客选择自提还是配送都应该显示
        if (!$this->config->get('config_checkout_pickup') || !$this->cart->shipping()) {
            return;
        }

       $data['is_pickup'] = array_get($this->session->data, 'is_pickup', false);

        return $this->load->view('checkout/checkout/_if_pickup', $data);
    }
    private function renderPickupSection() {
        $this->log(__FUNCTION__);

        // 商品本身不需要配送（虚拟商品类）时返回，购物车商品需要配送地址和配送方式时返回
        if (!$this->cart->shipping() || $this->hasShipping()) {
            return;
        }

       $data['pickup_id'] = array_get($this->session->data, 'pickup_id', 0);

        $this->load->model('localisation/pickup');

        $pickups = $this->model_localisation_pickup->getPickups();
        $data['pickups'] = array();
        foreach ($pickups as $pickup) {
            $data['pickups'][] = array(
                'pickup_id' => $pickup['pickup_id'],
                'name'      => $pickup['name']
            );
        }

        $pickup_info = $this->model_localisation_pickup->getPickup($data['pickup_id']);

        $data['country_id'] = isset($pickup_info['country_id']) ? $pickup_info['country_id'] : $this->config->get('config_country_id');
        $data['zone_id'] = isset($pickup_info['zone_id']) ? $pickup_info['zone_id'] : $this->config->get('config_zone_id');

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

        return $this->load->view('checkout/checkout/_pickup', $data);
    }

    private function renderShippingMethodSection()
    {
        $this->log(__FUNCTION__);
        $data['shipping'] = $this->hasShipping();

        if ($this->hasShipping()) {
            if (isset($this->session->data['shipping_address'])) {
                // Shipping Methods
                $this->model_checkout_checkout->getShippingMethods();
            }

            if (empty($this->session->data['shipping_methods'])) {
                $data['error_warning'] = sprintf(t('error_no_shipping'), $this->url->link('information/contact'));
            } else {
                $data['error_warning'] = '';
            }

            $data['shipping_methods'] = array_get($this->session->data, 'shipping_methods');
            $data['code'] = array_get($this->session->data, 'shipping_method.code');
        } else {
            $data['text_shipping_not_required'] = t('text_shipping_not_required');
        }

        return $this->load->view('checkout/checkout/_shipping_method', $data);
    }

    private function renderCommentSection()
    {
        $this->log(__FUNCTION__);
        $data['comment'] = array_get($this->session->data, 'comment', '');

        return $this->load->view('checkout/checkout/_comment', $data);
    }

    private function renderCartSection()
    {
        $this->log(__FUNCTION__);
        $data['products'] = $this->getProducts();
        $data['vouchers'] = $this->getVouchers();
        $data['recharges'] = $this->getRecharges();
        $data['totals'] = $this->getTotals();
        $data['comment_section'] = $this->renderCommentSection();

        return $this->load->view('checkout/checkout/_confirm', $data);
    }

    private function renderAgreeSection()
    {
        $this->log(__FUNCTION__);

        // Payment method
        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');
            $information_info = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
            if ($information_info) {
                $data['text_payment_method'] = sprintf(t('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_checkout_id')), $information_info['title'], $information_info['title']);
            } else {
                $data['text_payment_method'] = '';
            }
        } else {
            $data['text_payment_method'] = '';
        }

        $data['terms'] = (int)array_get($this->session->data, 'checkout_terms');

        return $this->load->view('checkout/checkout/_agree', $data);
    }

    // Private
    private function getProducts()
    {
        $this->load->model('tool/image');
        $products = array();

        foreach ($this->cart->getProducts() as $product) {
            $image = $this->model_tool_image->resize($product['image'] ?: 'placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_cart_height'));

            $option_data = array();
            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);
                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name' => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            $products[] = array(
                'cart_id' => $product['cart_id'],
                'product_id' => $product['product_id'],
                'image' => $image,
                'name' => $product['name'],
                'model' => $product['model'],
                'option' => $option_data,
                'quantity' => $product['quantity'],
                'subtract' => $product['subtract'],
                'price' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']),
                'total' => $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')) * $product['quantity'], $this->session->data['currency']),
                'href' => $this->url->link('product/product', 'product_id=' . $product['product_id'])
            );
        }

        return $products;
    }

    private function getVouchers()
    {
        $vouchers = array();

        if (empty($this->session->data['vouchers'])) {
            return $vouchers;
        }

        foreach ($this->session->data['vouchers'] as $voucher) {
            $vouchers[] = array(
                'description' => $voucher['description'],
                'amount' => $this->currency->format($voucher['amount'], $this->session->data['currency'])
            );
        }

        return $vouchers;
    }

    private function getRecharges()
    {
        $recharges = array();

        if (empty($this->session->data['recharges'])) {
            return $recharges;
        }

        foreach ($this->session->data['recharges'] as $recharge) {
            $recharges[] = array(
                'description' => $recharge['description'],
                'amount' => $this->currency->format($recharge['amount'], $this->session->data['currency'])
            );
        }

        return $recharges;
    }

    private function getTotals()
    {
        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        // Because __call can not keep var references so we put them into an array.
        $total_data = array(
            'totals' => &$totals,
            'taxes' => &$taxes,
            'total' => &$total
        );

        $this->load->model('setting/extension');
        $sort_order = array();
        $results = $this->model_setting_extension->getExtensions('total');
        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);
        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);

                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = array();
        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }
        array_multisort($sort_order, SORT_ASC, $totals);

        $results = array();

        foreach ($totals as $total) {
            $title          = $total['title'];
            if (in_array($total['code'], ['multiseller_shipping','multiseller_coupon'])) {
                $titles     = explode('&', $title);
                $title      = $titles[0] . $titles[2];
            }
            if ($total['code'] == 'shipping') continue;

            $results[] = array(
                'title' => $title,
                'text' => $this->currency->format($total['value'], $this->session->data['currency'])
            );
        }

        return $results;
    }

    // Address
    private function initAddressSession($type = 'shipping')
    {
        if ($type == 'shipping') {
            if (!$this->hasShipping()) {
                $this->log('Shipping not required.');
                unset($this->session->data['shipping_address']);
                unset($this->session->data['shipping_methods']);
                unset($this->session->data['shipping_method']);
                return;
            }
        }

        // Use previous selected address
        if ($addressId = array_get($this->session->data, "{$type}_address.address_id")) {
            $this->log("{$type}_address_id: {$addressId}");
            if ($address = $this->model_account_address->getAddress($addressId)) {
                $this->syncAddressSession($type, $address);
            } else {
                $this->log("{$type}_address_id: {$addressId} not found.");
                unset($this->session->data["{$type}_address"]);
                unset($this->session->data["{$type}_methods"]);
                unset($this->session->data["{$type}_method"]);
            }
        }

        // Use customer default address
        if (!array_get($this->session->data, "{$type}_address.address_id")) {
            $address = $this->model_account_address->getAddress($this->customer->getAddressId());
            if ($address) {
                $this->syncAddressSession($type, $address);
            } else {
                unset($this->session->data["{$type}_address"]);
                unset($this->session->data["{$type}_methods"]);
                unset($this->session->data["{$type}_method"]);
            }
        }

        // User customer first address
        if (!array_get($this->session->data, "{$type}_address.address_id")) {
            $addresses = $this->model_account_address->getAddresses();
            if ($addresses) {
                $firstAddress = reset($addresses);
                $this->syncAddressSession($type, $firstAddress);
            } else {
                unset($this->session->data["{$type}_address"]);
                unset($this->session->data["{$type}_methods"]);
                unset($this->session->data["{$type}_method"]);
            }
        }

        // Use dummy address
        if (!array_get($this->session->data, "{$type}_address.address_id")) {
            $this->fakeGuestAddressSession($type);
        }
    }

    private function syncAddressSession($type, $address)
    {
        if (!in_array($type, ['payment', 'shipping'])) {
            return false;
        }

        if ($type == 'shipping' && !$this->hasShipping()) {
            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['shipping_method']);
            return false;
        }

        $this->session->data["{$type}_address"] = $address;

        $method = 'set' . ucfirst($type) . 'Method';
        if ($code = array_get($this->session->data, "{$type}_method.code")) {
            if (!$this->model_checkout_checkout->{$method}($code)) {
                $this->model_checkout_checkout->{$method}();
            }
        } else {
            $this->model_checkout_checkout->{$method}();
        }
    }

    private function fakeGuestAddressSession($type)
    {
        if (!in_array($type, ["payment", "shipping"])) {
            return;
        }

        $this->session->data[$type . '_address'] = array();
        foreach ($this->ADDRESS_FIELDS as $field) {
            $this->session->data[$type . '_address'][$field] = '';
        }

        $this->session->data[$type . '_address']['country_id'] = $this->model_checkout_checkout->getDefaultCountryId();
        $this->session->data[$type . '_address']['zone_id'] = $this->model_checkout_checkout->getDefaultZoneId();

        $this->syncAddressSession($type, $this->session->data[$type . '_address']);
    }

    protected function validateAddress()
    {
        $this->load->language('account/address');

        $error = [];
        if ((utf8_strlen(trim($this->request->post['fullname'])) < 1) || (utf8_strlen(trim($this->request->post['fullname'])) > 32)) {
            $error['fullname'] = t('error_fullname');
        }

        if ((utf8_strlen(trim($this->request->post['telephone'])) < 5) || (utf8_strlen(trim($this->request->post['telephone'])) > 32)) {
            $error['telephone'] = t('error_telephone');
        }

        if ((utf8_strlen(trim($this->request->post['address_1'])) < 3) || (utf8_strlen(trim($this->request->post['address_1'])) > 128)) {
            $error['address_1'] = t('error_address_1');
        }

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->request->post['country_id']);

        if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($this->request->post['postcode'])) < 2 || utf8_strlen(trim($this->request->post['postcode'])) > 10)) {
            $error['postcode'] = t('error_postcode');
        }

        if ($this->request->post['country_id'] == '' || !is_numeric($this->request->post['country_id'])) {
            $error['country_id'] = t('error_country');
        }

        if (!isset($this->request->post['zone_id']) || $this->request->post['zone_id'] == '' || !is_numeric($this->request->post['zone_id'])) {
            $error['zone_id'] = t('error_zone');
        }

        if (!is_ft()) {
            // 中文版需要验证省市县三级必填
            if (!isset($this->request->post['city_id']) || $this->request->post['city_id'] == '' || !is_numeric($this->request->post['city_id'])) {
                $error['city_id'] = t('error_city_id');
            }

            if (!isset($this->request->post['county_id']) || $this->request->post['county_id'] == '' || !is_numeric($this->request->post['county_id'])) {
                $error['county_id'] = t('error_county_id');
            }

            $this->request->post['city'] = '';

        } else { // 非中文版需要验证验证 city 输入框
            if ((utf8_strlen(trim($this->request->post['city'])) < 2) || (utf8_strlen(trim($this->request->post['city'])) > 128)) {
                $error['city'] = t('error_city');
            }

            $this->request->post['city_id'] = 0;
            $this->request->post['county_id'] = 0;
        }

        // Custom field validation
        $this->load->model('account/custom_field');
        $custom_fields = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
        foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] != 'address') {
                continue;
            }
            if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                $error["custom_field_{$custom_field['custom_field_id']}"] = sprintf(t('error_custom_field'), $custom_field['name']);
            } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                $error["custom_field_{$custom_field['custom_field_id']}"] = sprintf(t('error_custom_field'), $custom_field['name']);
            }
        }
        return $error;
    }

    private function log($data = null)
    {
        if ($data) {
            $this->model_checkout_checkout->log($data);
        }
    }

    // Original
    public function country()
    {
        $json = array();

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

        if ($country_info) {
            $this->load->model('localisation/zone');

            $json = array(
                'country_id' => $country_info['country_id'],
                'name' => $country_info['name'],
                'iso_code_2' => $country_info['iso_code_2'],
                'iso_code_3' => $country_info['iso_code_3'],
                'address_format' => $country_info['address_format'],
                'postcode_required' => $country_info['postcode_required'],
                'zone' => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
                'status' => $country_info['status']
            );
        }

        $this->jsonOutput($json);
    }

    public function customfield()
    {
        $json = array();

        $this->load->model('account/custom_field');

        // Customer Group
        if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
            $customer_group_id = $this->request->get['customer_group_id'];
        } else {
            $customer_group_id = $this->config->get('config_customer_group_id');
        }

        $custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

        foreach ($custom_fields as $custom_field) {
            $json[] = array(
                'custom_field_id' => $custom_field['custom_field_id'],
                'required' => $custom_field['required']
            );
        }

        parent::jsonOutput($json);
    }

    public function pickup() {
        $json = array();

        $this->load->model('localisation/zone');

        $zone_info = $this->model_localisation_zone->getZone($this->request->get['zone_id']);

        if ($zone_info) {
            $this->load->model('localisation/pickup');

            $json = array(
                'zone_id'           => $zone_info['zone_id'],
                'name'              => $zone_info['name'],
                'pickups'           => $this->model_localisation_pickup->getPickupsByZoneId($this->request->get['zone_id'])
            );
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
