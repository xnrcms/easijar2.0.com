<?php

class ModelExtensionTotalPaymentHandling extends Model
{
    public function getTotal($total)
    {
        if (!isset($this->session->data['payment_method']) || empty($this->session->data['payment_method'])) {
            return;
        }
        $currentPaymentMethod = $this->session->data['payment_method'];
        $subTotal = $this->cart->getSubTotal();
        if (($subTotal > $this->config->get('total_payment_handling_total')) && ($this->cart->getSubTotal() > 0)) {
            $this->load->language('extension/total/payment_handling');

            $code = $currentPaymentMethod['code'];
            $rateKey = "total_payment_handling_{$code}_rate";
            $flatKey = "total_payment_handling_{$code}_flat";
            $rate = $this->config->get($rateKey);
            $fee = $this->config->get($flatKey);
            if (!$rate && !$fee) {
                return;
            }

            $handlingFee = $subTotal * $rate / 100 + $fee;
            $total['totals'][] = array(
                'code' => 'payment_handling',
                'title' => $this->language->get('text_handling'),
                'value' => number_format($handlingFee, 2),
                'sort_order' => $this->config->get('total_payment_handling_sort_order')
            );

            $total['total'] += (float)number_format($handlingFee, 2);
        }
    }
}