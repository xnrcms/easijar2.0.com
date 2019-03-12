<?php
class ControllerExtensionTotalTotals extends Controller
{
	public function getTotals($order_info = [])
    {
        $order_id           = isset($order_info['order_id']) ? (int)$order_info['order_id'] : 0;
        $seller_id          = isset($order_info['seller_id']) ? (int)$order_info['seller_id'] : 0;

        if ($order_id <= 0 || $seller_id <= 0 ) return [];

        $this->load->model('account/order');

        $ms_order_products  = $this->model_account_order->getOrderProductsForMs($order_id,0);
        if (empty($ms_order_products)) return [];

        $all_totals         = $this->model_account_order->getTotals($order_id);
        if (empty($all_totals)) return [];

        $all_sub_total              = 0;
        $seller_total           	= 0;
        $seller_sub_total           = 0;
        $platform_coupon            = 0;
        $seller_coupon              = 0;
        $seller_shipping            = 0;
        $seller_platform_coupon     = 0;

        foreach ($ms_order_products as $mskey => $msvalue)
        {
            $all_sub_total          += $msvalue['total'];
            $ms_seller_id           = (int)$msvalue['seller_id'];

            if ($seller_id > 0 && $ms_seller_id === $seller_id)
            {
                $seller_sub_total   += $msvalue['total'];
            }

            //计算total
            foreach ($all_totals as $atkey => $atvalue)
            {
                //使用店铺运费金额
                $stitle      = '&#' . $ms_seller_id . 'multiseller_shipping&#Multi-seller Shipping Fee';
                if ( $atvalue['code'] === 'multiseller_shipping' && strpos($atvalue['title'],$stitle) !== false && $atvalue['value'] > 0) {
                    $all_sub_total    += $atvalue['value'];
                    if ($seller_id > 0 && $ms_seller_id === $seller_id) {
                        $seller_sub_total    += $atvalue['value'];
                        $seller_shipping     += $atvalue['value'];
                    }
                }

                //使用店铺优惠券金额
                $ctitle      = '&#' . $seller_id . 'multiseller_coupon&#Coupon';
                if ( $atvalue['code'] === 'multiseller_coupon' && strpos($atvalue['title'],$ctitle) !== false && $atvalue['value'] > 0) {
                    $all_sub_total    += $atvalue['value'];
                    if ($seller_id > 0 && $ms_seller_id === $seller_id) {
                        $seller_sub_total    += $atvalue['value'];
                        $seller_coupon       += $atvalue['value'];
                    }
                }
            }
        }

        foreach ($all_totals as $atkey => $atvalue)
        {
            //平台优惠券
            $ptitle      = '&#0multiseller_coupon&#';
            if ( $atvalue['code'] === 'multiseller_coupon' && strpos($atvalue['title'],$ptitle) !== false && $atvalue['value'] > 0) {
                $platform_coupon    = $atvalue['value'];
            }
        }

        $seller_platform_coupon     = ($seller_sub_total / $all_sub_total) * $platform_coupon;
        $seller_total           	= $seller_sub_total - $seller_platform_coupon;
        
        return [
        	'seller_sub_total' 			=> $this->currency->format($seller_sub_total, $order_info['currency_code'], $order_info['currency_value'], $this->session->data['currency']),
            'seller_total'       		=> $this->currency->format($seller_total, $order_info['currency_code'], $order_info['currency_value'], $this->session->data['currency']),
            'seller_coupon'     		=> $this->currency->format($seller_coupon, $order_info['currency_code'], $order_info['currency_value'], $this->session->data['currency']),
            'seller_shipping'   		=> $this->currency->format($seller_shipping, $order_info['currency_code'], $order_info['currency_value'], $this->session->data['currency']),
            'seller_platform_coupon'   	=> $this->currency->format($seller_platform_coupon, $order_info['currency_code'], $order_info['currency_value'], $this->session->data['currency']),
        ];
    }
}
