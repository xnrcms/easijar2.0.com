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

class ControllerApiCheckout extends Controller
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
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','selected'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $json       = [];

        if (!$this->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        if (!$this->isValidCart()){
            return $this->response->setOutput($this->returnData(['msg'=>'fail:ValidCart is error']));
        }

        //校验是否勾选额购物车商品
        if (isset($req_data['selected']) && !empty($req_data['selected']) && is_string($req_data['selected'])) {
            $selected       = explode(',', $req_data['selected']);
            $this->cart->select($selected);
        } else {
            return $this->response->setOutput($this->returnData(['code'=>'202','msg'=>'cart select error']));
        }

        // Shipping address
        $this->initAddressSession('shipping');

        // Payment address
        $this->initAddressSession('payment');

        // Init pickup
        if ($this->hasShipping()) {
            unset($this->session->data['pickup_id']);
        }

        /*if ($this->hasShipping()) {
            $this->log($this->session->data['shipping_address']);
        }

        $this->log($this->session->data['payment_address']);*/

        /*$json['logged']                         = $this->isLogged();
        $json['shipping_required']              = $this->hasShipping();*/
        //$json['payment_address_required']       = $this->isPaymentAddressRequired();

        if ($this->hasShipping()) {
            $shipping_address_section           = $this->renderAddressSection('shipping');
            $address                            = isset($shipping_address_section['addresses']) ? $shipping_address_section['addresses'] : [];
            $shipping_option                    = [];
            foreach ($address as $key => $value) {
                $shipping_option[]              = [
                    'address_id'            => $value['address_id'],
                    'fullname'              => $value['fullname'],
                    'telephone'             => $value['telephone'],
                    'company'               => $value['company'],
                    'address_1'             => $value['address_1'],
                    'address_2'             => $value['address_2'],
                    'postcode'              => $value['postcode'],
                    'country_id'            => $value['country_id'],
                    'country'               => $value['country']
                ];
            }
            $json['shipping_address_section']   = $shipping_option;
        }else{
            $json['shipping_address_section']   = [];
        }

        $payment_method_section                 = $this->renderPaymentMethodSection();
        $payment_method                         = isset($payment_method_section['payment_methods']) ? $payment_method_section['payment_methods'] : [];
        $payment_option                         = [];
        foreach ($payment_method as $key => $value) {
            $payment_option[]                   = ['code' => $value['code'],'title' => $value['title']];
        }
        $json['payment_method_section']         = $payment_option;

        /*if ($this->isPaymentAddressRequired()) {
            $payment_address_section            = $this->renderAddressSection('payment');
            $json['payment_address_section']    = $this->renderAddressSection('payment');
        }else{
            $json['payment_address_section']    = [];
        }*/

        //$json['if_pickup_section']              = $this->renderIfPickupSection();
        //$json['pickup_section']                 = $this->renderPickupSection();
        //$json['shipping_method_section']        = $this->renderShippingMethodSection();
        //$json['payment_method_section']         = $this->renderPaymentMethodSection();

        $products                               = $this->renderCartSection();
        $cart_products                          = [];

        if ( isset($products['products']) && !empty($products['products'])) {

            $this->load->language('extension/total/multiseller_shipping', 'multiseller');
            $total_title    = $this->language->get('multiseller')->get('text_multiseller_shipping');

            $totals         = isset($products['totals']) ? $products['totals'] : [];
            $seller_ship    = [];
            $ship_del       = [];

            foreach ($totals as $tk => $tv) {
                if (strpos('&'.$tv['title'], '平台商品运费') >= 1 || strpos('&'.$tv['title'], 'Platform shipping fee') >= 1) {
                    unset($totals[$tk]);continue;
                }

                $seller_ship[$tv['title']]  = $tv['text'];
                $ship_del[$tv['title']]     = $tk;
            }

            foreach ($products['products'] as $key => $value) {
                //unset($value['href']);
                $store_name_text         = $value['store_name'] . ' ' . $total_title;

                //删除数组元素
                if (isset($seller_ship[$store_name_text])) {
                    $shipping           = $seller_ship[$store_name_text];
                    unset($totals[$ship_del[$store_name_text]]);
                }else{
                    $shipping           = '';
                }

                $value['shipping']       = $shipping;
                $value['cat_type']       = (isset($this->session->data['buy_type']) ? $this->session->data['buy_type'] : 0);

                $goods                   = isset($value['products']) ? $value['products'] : [];
                $ggs                     = [];

                foreach ($goods as $gk => $gv) {
                    $gopt       = isset($gv['option']) ? $gv['option'] : [];
                    $opt_text   = '';
                    foreach ($gopt as $oval) {
                        $opt_text   .= $oval['name'] . ':' . $oval['value'] . ',';
                    }

                    $ggs[]              = [
                        'cart_id'       => $gv['cart_id'],
                        'name'          => $gv['name'],
                        'quantity'      => $gv['quantity'],
                        'product_id'    => $gv['product_id'],
                        'image'         => $gv['cart_id'],
                        'option'        => trim($opt_text,','),
                    ];
                }

                $value['products']       = $ggs;
                $cart_products[]         = $value;
            }
        }

        sort($totals);

        $products['totals']                     = $totals;
        $products['products']                   = $cart_products;

        $json['cart_section']                   = $products;



        //$json['comment_section']                = $this->renderCommentSection();
        //$json['agree_section']                  = $this->renderAgreeSection();

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$json]));
    }

    // Validate and submit order
    public function confirm()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','shipping_address_id','payment_method','shipping_method','comment'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!$this->isLogged()){
            return $this->response->setOutput($this->returnData(['code'=>'201','msg'=>t('warning_login')]));
        }

        if (!$this->isValidCart()){
            return $this->response->setOutput($this->returnData(['msg'=>'fail:ValidCart is error']));
        }

        if (!(isset($req_data['shipping_address_id']) && intval($req_data['shipping_address_id']) > 0 )) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:shipping_address_id is error']));
        }

        if (!(isset($req_data['payment_method']) && !empty($req_data['payment_method']) > 0 )) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:payment_method is error']));
        }

        $this->log(__FUNCTION__);
        

        $this->log($this->request->post);

        $order_data                         = [];
        $order_data['payment_address']      = [];
        $order_data['shipping_address']     = [];

        // Shipping address
        if ($this->hasShipping()) {
            $addressId                      = (int)array_get($req_data, 'shipping_address_id');
            if (!$addressId) {
                return $this->response->setOutput($this->returnData(['msg'=>t('error_address')]));
            }

            $address                        = $this->model_account_address->getAddress($addressId);
            if (!$address) { // Selected address not exists anymore
                return $this->response->setOutput($this->returnData(['msg'=>t('error_address_not_exist')]));
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
                return $this->response->setOutput($this->returnData(['msg'=>t('error_pickup')]));
            }
        }

        // Payment address
        /*if ($this->isPaymentAddressRequired()) {
            $addressId = (int)array_get($req_data, 'payment_address_id');
            if (! $addressId) {
                return $this->response->setOutput($this->returnData(['msg'=>t('error_address')]));
            }

            $address = $this->model_account_address->getAddress($addressId);
            if (!$address) { // Selected address not exists anymore
                return $this->response->setOutput($this->returnData(['msg'=>t('error_address_not_exist')]));
            }

            $order_data['payment_address']  = $address;
            $this->syncAddressSession('payment', $address);
        }*/

        // Payment method
        if (!array_get($req_data, 'payment_method')) {
            return $this->response->setOutput($this->returnData(['msg'=>t('error_payment')]));
        } else {
            $code = array_get($req_data, 'payment_method');
            if (!$this->model_checkout_checkout->setPaymentMethod($code)) {
                return $this->response->setOutput($this->returnData(['msg'=>t('error_payment_unavailable')]));
            } else {
                $order_data['payment_method'] = $code;
            }
        }

        // Shipping method
        if ($this->hasShipping()) {
            if (!array_get($req_data, 'shipping_method')) {
                return $this->response->setOutput($this->returnData(['msg'=>t('error_shipping')]));
            } else {
                $code = array_get($req_data, 'shipping_method');
                if (!$this->model_checkout_checkout->setShippingMethod($code)) {
                    return $this->response->setOutput($this->returnData(['msg'=>t('error_shipping_unavailable')]));
                } else {
                    $shipping                       = explode('.', $code);
                    $order_data['shipping_method']  = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
                }
            }
        } else {
            unset($this->session->data['shipping_methods']);
            unset($this->session->data['shipping_method']);
        }

        // Comment
        $order_data['comment']                          = array_get($req_data, 'comment', '');

        // Terms & conditions agreement
        if ($this->config->get('config_checkout_id')) {
            $this->load->model('catalog/information');
            $information = $this->model_catalog_information->getInformation($this->config->get('config_checkout_id'));
            if ($information && !array_get($this->request->post, 'terms')) {
                return $this->response->setOutput($this->returnData(['msg'=>sprintf(t('error_agree'), $information['title'])]));
            }
        }

        // ALL set, update address session then submit the order
        $this->session->data['payment_address'] = $order_data['payment_address'];
        if ($this->hasShipping()) {
            $this->session->data['shipping_address'] = $order_data['shipping_address'];
        } else {
            unset($this->session->data['shipping_address']);
        }
        //return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$order_data]));
        try {
            // Comment
            $this->session->data['comment'] = $order_data['comment'];

            $order_id = $this->model_checkout_checkout->createOrder();
            $this->cart->clear();

            // Change order status to Unpaid
            if ($order_data['payment_method'] != 'cod') {

                $this->session->data['order_sn'] = $this->model_checkout_checkout->getOrderSnByOrderId($order_id);

                $payment_view          = $this->load->controller("extension/payment/" . $order_data['payment_method'] . '/payFormForSm');
                $this->model_checkout_order->addOrderHistory($order_id, config('config_unpaid_status_id'));
            } else { // cod order does not need unpaid status
                $payment_view          = '';
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_cod_order_status_id'));
            }

            $ret                            = [];
            $ret['payment_method']          = $order_data['payment_method'];
            $ret['payment_view']            = $payment_view;

            return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>$ret]));
        } catch (\Exception $e) {
            return $this->response->setOutput($this->returnData(['msg'=>$e->getMessage()]));
        }
    }

    public function reload()
    {
        if ($this->hasShipping()) {
            $data['shipping_address_section'] = $this->renderAddressSection('shipping');
        }

        if ($this->isPaymentAddressRequired()) {
            $data['payment_address_section'] = $this->renderAddressSection('payment');
        }

        $data['if_pickup_section']          = $this->renderIfPickupSection();
        $data['pickup_section']             = $this->renderPickupSection();
        $data['payment_method_section']     = $this->renderPaymentMethodSection();
        $data['shipping_method_section']    = $this->renderShippingMethodSection();
        $data['cart_section']               = $this->renderCartSection();
        $data['comment_section']            = $this->renderCommentSection();
        $data['agree_section']              = $this->renderAgreeSection();

        $this->response->setOutput($this->load->view('checkout/checkout/_main_section', $data));
    }

    // Address form
    public function address_form()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','address_id','type'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if ($this->checkSign($req_data)) {
            
            $json       = [];

            if (!$this->isLogged()){
                $json['code']       = '201';
                $json['msg']        = t('warning_login');
                return $this->response->setOutput($this->returnData($json));
            }

            if ($address_id = array_get($req_data, 'address_id')) {
                $address = $this->model_account_address->getAddress($address_id);
                if (!$address) {
                    $address_id = 0;
                } else {
                    $json['fullname']       = $address['fullname'];
                    $json['telephone']      = $address['telephone'];
                    $json['company']        = $address['company'];
                    $json['address_1']      = $address['address_1'];
                    $json['address_2']      = $address['address_2'];
                    $json['postcode']       = $address['postcode'];
                    $json['city']           = $address['city'];
                    $json['zone_id']        = $address['zone_id'];
                    $json['zone']           = $address['zone'];
                    $json['zone_code']      = $address['zone_code'];
                    $json['country_id']     = $address['country_id'];
                    $json['country']        = $address['country'];
                    $json['city_id']        = $address['city_id'];
                    $json['county_id']      = $address['county_id'];
                    //$json['address_custom_field'] = $address['custom_field'];
                    $json['default']        = $this->customer->getAddressId() == $address['address_id'];
                }
            }

            if (!$address_id) {
                $data['country_id']         = array_get($this->session->data, 'shipping_address.country_id', config('config_country_id'));
                $data['zone_id']            = array_get($this->session->data, 'shipping_address.zone_id', config('config_zone_id'));
                $data['postcode']           = array_get($this->session->data, 'shipping_address.postcode');
            }

            $this->load->model('localisation/country');
            $data['countries']              = $this->model_localisation_country->getCountries();

            // Custom Fields
            $this->load->model('account/custom_field');
            $custom_fields                  = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
            $data['custom_fields']          = [];
            foreach ($custom_fields as $custom_field) {
                if ($custom_field['location'] == 'address') {
                    $data['custom_fields'][]    = $custom_field;
                }
            }

            $data['address_id']             = $address_id;
            $data['type']                   = array_get($req_data, 'type', 'shipping');


            $json       = $this->returnData(['code'=>'200','msg'=>'success','data'=>$json]);
        }else{

            $json       = $this->returnData(['msg'=>'fail:sign error']);
        }

        return $this->response->setOutput($json);
    }

    public function save_address()
    {
        $this->response->addHeader('Content-Type: application/json');

        $allowKey       = ['api_token','fullname','telephone','address_1','address_2','country_id','zone_id','city','postcode','default','address_id','type'];
        $req_data       = $this->dataFilter($allowKey);
        $json           =  $this->returnData();

        if ($this->checkSign($req_data)) {

            $json       = [];

            if (!$this->isLogged()){
                $json['code']       = '201';
                $json['msg']        = t('warning_login');
                return $this->response->setOutput($this->returnData($json));
            }

            $error          = $this->validateAddress($req_data);
            if (isset($error[0]) && $error[0] == 'ok') {
                $req_data   = $error[1];

                $addressId  = (int)array_get($req_data, 'address_id');
                if ($addressId > 0) {
                    $this->model_account_address->editAddress($addressId, $req_data);
                } else {
                    $addressId = $this->model_account_address->addAddress($this->customer->getId(), $req_data);
                }

                $address        = $this->model_account_address->getAddress($addressId);
                $type           = array_get($req_data, 'type', 'shipping');
                $this->syncAddressSession($type, $address);

                if ($type == 'shipping' && empty($this->session->data["payment_address"]['address_id'])) {
                    $this->syncAddressSession('payment', $address);
                }

                $json       = $this->returnData(['code'=>'200','msg'=>'success']);
            }else{
                return $this->response->setOutput($this->returnData(['msg'=>$error]));
            }

        }else{

            $json       = $this->returnData(['msg'=>'fail:sign error']);
        }

        return $this->response->setOutput($json);
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
        $data['logged']     = $this->isLogged();

        $address_id         = (int)array_get($this->session->data, "{$type}_address.address_id");
        if ($address_id) {
            if(! $this->model_account_address->getAddress($address_id)) {
                $address_id = 0;
            }
        }

        $data['address_id'] = $address_id ?: $this->customer->getAddressId();
        $data['addresses']  = $this->model_account_address->getAddresses();

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

        $this->load->view("checkout/checkout/_{$type}_address", $data);

        return $this->load->getViewData('addresses');
    }

    private function renderPaymentMethodSection()
    {
        $this->log(__FUNCTION__);
        if (isset($this->session->data['payment_address'])) {
            $this->model_checkout_checkout->getPaymentMethods();
        }

        /*if (empty($this->session->data['payment_methods'])) {
            $data['error_warning'] = sprintf(t('error_no_payment'), $this->url->link('information/contact'));
        } else {
            $data['error_warning'] = '';
        }*/

        if (isset($this->session->data['payment_methods'])) {
            $data['payment_methods'] = $this->session->data['payment_methods'];
        } else {
            $data['payment_methods'] = [];
        }

        if (isset($this->session->data['payment_method']['code'])) {
            $data['code'] = $this->session->data['payment_method']['code'];
        } else {
            $data['code'] = '';
        }

        /*if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }*/

        $this->load->view('checkout/checkout/_payment_method', $data);

        return $this->load->getViewData('code,payment_methods');
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
        $data['shipping'] = $this->hasShipping();

        if ($this->hasShipping()) {
            if (isset($this->session->data['shipping_address'])) {
                // Shipping Methods
                $this->model_checkout_checkout->getShippingMethods();
            }

            /*if (empty($this->session->data['shipping_methods'])) {
                $data['error_warning'] = sprintf(t('error_no_shipping'), $this->url->link('information/contact'));
            } else {
                $data['error_warning'] = '';
            }*/

            $data['shipping_methods']   = array_get($this->session->data, 'shipping_methods');
            $data['code']               = array_get($this->session->data, 'shipping_method.code');

            $this->load->view('checkout/checkout/_shipping_method', $data);

            return $this->load->getViewData('code,shipping_methods');
        } 

        return [];
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
        $data['products']   = $this->getProducts();
        //$data['vouchers']   = $this->getVouchers();
        //$data['recharges']  = $this->getRecharges();
        $data['totals']     = $this->getTotals();

        $this->load->view('checkout/checkout/_confirm', $data);

        return $this->load->getViewData('products,vouchers,recharges,totals');
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
            $results[] = array(
                'title' => $total['title'],
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

    protected function validateAddress($req_data)
    {
        $this->load->language('account/address');

        if ((utf8_strlen(trim($req_data['fullname'])) < 1) || (utf8_strlen(trim($req_data['fullname'])) > 32)) {
            return t('error_fullname');
        }

        if ((utf8_strlen(trim($req_data['telephone'])) < 5) || (utf8_strlen(trim($req_data['telephone'])) > 32)) {
            return t('error_telephone');
        }

        if ((utf8_strlen(trim($req_data['address_1'])) < 3) || (utf8_strlen(trim($req_data['address_1'])) > 128)) {
            return t('error_address_1');
        }

        /*if ((utf8_strlen(trim($req_data['address_2'])) < 3) || (utf8_strlen(trim($req_data['address_2'])) > 128)) {
            return t('error_address_2');
        }*/

        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($req_data['country_id']);

        if ($country_info && $country_info['postcode_required'] && (utf8_strlen(trim($req_data['postcode'])) < 2 || utf8_strlen(trim($req_data['postcode'])) > 10)) {
            return t('error_postcode');
        }

        if ($req_data['country_id'] == '' || !is_numeric($req_data['country_id'])) {
            return t('error_country');
        }

        if (!isset($req_data['zone_id']) || $req_data['zone_id'] == '' || !is_numeric($req_data['zone_id'])) {
            return t('error_zone');
        }

        if (!is_ft()) {
            // 中文版需要验证省市县三级必填
            if (!isset($req_data['city_id']) || $req_data['city_id'] == '' || !is_numeric($req_data['city_id'])) {
                return t('error_city_id');
            }

            if (!isset($req_data['county_id']) || $req_data['county_id'] == '' || !is_numeric($req_data['county_id'])) {
                return t('error_county_id');
            }

            $req_data['city']       = '';

        } else { // 非中文版需要验证验证 city 输入框
            if ((utf8_strlen(trim($req_data['city'])) < 2) || (utf8_strlen(trim($req_data['city'])) > 128)) {
                return t('error_city');
            }

            $req_data['city_id']        = 0;
            $req_data['county_id']      = 0;
        }

        // Custom field validation
        $this->load->model('account/custom_field');
        $custom_fields              = $this->model_account_custom_field->getCustomFields($this->config->get('config_customer_group_id'));
        /*foreach ($custom_fields as $custom_field) {
            if ($custom_field['location'] != 'address') {
                continue;
            }
            if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
                $error["custom_field_{$custom_field['custom_field_id']}"] = sprintf(t('error_custom_field'), $custom_field['name']);
            } elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
                $error["custom_field_{$custom_field['custom_field_id']}"] = sprintf(t('error_custom_field'), $custom_field['name']);
            }
        }*/
        return ['ok',$req_data];
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