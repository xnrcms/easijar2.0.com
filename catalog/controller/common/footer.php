<?php
class ControllerCommonFooter extends Controller {
    public function index() {
        $this->load->language('common/footer');

        $this->load->model('catalog/information');

        $data['informations'] = array();

        foreach ($this->model_catalog_information->getInformations() as $result) {
            if ($result['bottom']) {
                $data['informations'][] = array(
                    'title' => $result['title'],
                    'href'  => $this->url->link('information/information', 'information_id=' . $result['information_id'])
                );
            }
        }

        if (config('is_mobile') && current_route() == 'product/product') {
            if (isset($this->request->get['product_id'])) {
                $product_id = (int)$this->request->get['product_id'];
            } else {
                $product_id = 0;
            }

            $this->load->model('catalog/product');

            $product_info = $this->model_catalog_product->getProduct($product_id);

            $data['quantity'] = $product_info['quantity'];
        }

        $data['cart_count'] = $this->cart->countProducts();

        $data['contact'] = $this->url->link('information/contact');
        $data['return'] = $this->url->link('account/return/add');
        $data['sitemap'] = $this->url->link('information/sitemap');
        $data['tracking'] = $this->url->link('information/tracking');
        $data['faq'] = $this->url->link('information/faq');
        $data['manufacturer'] = $this->url->link('product/manufacturer');
        $data['voucher'] = $this->url->link('account/voucher');
        $data['affiliate'] = $this->url->link('affiliate/login');
        $data['special'] = $this->url->link('product/special');
        $data['latest'] = $this->url->link('product/latest');
        $data['account'] = $this->url->link('account/account');
        $data['shopping_cart'] = $this->url->link('checkout/cart');
        $data['checkout'] = $this->url->link('checkout/checkout');
        $data['order'] = $this->url->link('account/order');
        $data['wishlist'] = $this->url->link('account/wishlist');
        $data['newsletter'] = $this->url->link('account/newsletter');

        // Custom Copyright: `theme_default_copyright.1`
        if ($powered = config('theme_' . config('config_theme') . '_copyright.' . current_language_id())) {
            $data['powered'] = html_entity_decode($powered, ENT_QUOTES, 'UTF-8');
        } else {
            $data['powered'] = sprintf($this->language->get('text_powered'), $this->config->get('config_name'), date('Y', time()));
        }

        // Whois Online
        if ($this->config->get('config_customer_online')) {
            $this->load->model('tool/online');

            if (isset($this->request->server['REMOTE_ADDR'])) {
                $ip = $this->request->server['REMOTE_ADDR'];
            } else {
                $ip = '';
            }

            if (isset($this->request->server['HTTP_HOST']) && isset($this->request->server['REQUEST_URI'])) {
                $url = ($this->request->server['HTTPS'] ? 'https://' : 'http://') . $this->request->server['HTTP_HOST'] . $this->request->server['REQUEST_URI'];
            } else {
                $url = '';
            }

            if (isset($this->request->server['HTTP_REFERER'])) {
                $referer = $this->request->server['HTTP_REFERER'];
            } else {
                $referer = '';
            }

            $this->model_tool_online->addOnline($ip, $this->customer->getId(), $url, $referer);
        }

        $data['route'] = array_get($this->request->get, 'route', 'common/home');

        // modal view, no header logo, menu etc.
        $data['modal'] = (bool)array_get($this->request->get, 'modal');

        // Customer service
        if (!$data['modal'] && !config('is_mobile')) {
            if ($services = config('theme_' . config('config_theme') . '_customer_service')) {
                $data['services'] = [];
                foreach ($services as $service) {
                    $image = array_get($service, 'image');
                    $title = array_get($service, 'title.' . current_language_id());
                    $subtitle = array_get($service, 'subtitle.' . current_language_id());

                    if (empty($title) && empty($subtitle)) {
                        continue;
                    }

                    $data['services'][] = array(
                        'image'    => $image,
                        'title'    => $title,
                        'subtitle' => $subtitle
                    );
                }
            }
        }

        $data['scripts'] = $this->document->getScripts('footer');

        return $this->load->view('common/footer', $data);
    }
}
