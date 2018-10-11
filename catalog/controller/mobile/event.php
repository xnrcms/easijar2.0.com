<?php

/**
 * @copyright        2017 opencart.cn - All Rights Reserved
 * @author:          Sam Chen <sam.chen@opencart.cn>
 * @created:         2018-01-12 15:04:24
 * @modified by:     Sam Chen <sam.chen@opencart.cn>
 * @modified:        2018-02-05 12:27:36
 */

class ControllerMobileEvent extends Controller {
    public function viewProductCategoryBefore(&$route, &$data, &$template)
    {
        if (!config('is_mobile')) {
            return;
        }

        // Format sort data for mobile
        if (isset($data['sorts'])) {
            $data = $this->formatProductListingPageSorts($data);
        }
    }

    private function formatProductListingPageSorts($data)
    {
        $sort_formatted = [];
        $sorts_formatted[] = array(
          'text'   => $data['sorts'][0]['text'],
          'href'   => $data['sorts'][0]['href'],
          'active' => $data['sort'] . '-' . $data['order'] == $data['sorts'][0]['value'],
          'type'   => true
        );

        for ($i = 1; $i < count($data['sorts']); $i += 2) {
            $sorts_formatted[] = array(
              'text'   => mb_substr($data['sorts'][$i]['text'], 0, mb_strpos($data['sorts'][$i]['text'], ' ')),
              'href'   => $data['sort'] . '-' . $data['order'] == $data['sorts'][$i]['value'] ? $data['sorts'][$i + 1]['href'] : $data['sorts'][$i]['href'],
              'active' => ($data['sort'] . '-' . $data['order'] == $data['sorts'][$i]['value'] || $data['sort'] . '-' . $data['order'] == $data['sorts'][$i + 1]['value']),
              'type'   => $data['sort'] . '-' . $data['order'] == $data['sorts'][$i]['value'] ? false : true
            );
        }

        $data['sorts'] = $sorts_formatted;
        return $data;
    }

    public function viewCommonFooterBefore(&$route, &$data, &$template)
    {
        if (!config('is_mobile')) {
            return;
        }

        $data['cart_count'] = $this->cart->countProducts();
        $data['language'] = $this->load->controller('common/language');
        $data['currency'] = $this->load->controller('common/currency');

        // Add some data for product page
        if (array_get($this->request->get, 'route') == 'product/product' && isset($this->request->get['product_id'])) {
            $product_id = $this->request->get['product_id'];
            $this->load->model('catalog/product');
            if ($this->customer->isLogged()) {
                $data['in_wishlist'] = $this->model_catalog_product->inWishlist($product_id);
            }

            $product_info = $this->model_catalog_product->getProduct($product_id);
            $data['quantity'] = $product_info['quantity'];
        }
    }
}
