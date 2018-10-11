<?php

class ControllerExtensionTotalPaymentHandling extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/total/payment_handling');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('total_payment_handling', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total'));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_none'] = $this->language->get('text_none');

        $data['entry_total'] = $this->language->get('entry_total');
        $data['entry_rate'] = $this->language->get('entry_rate');
        $data['entry_flat'] = $this->language->get('entry_flat');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['help_total'] = $this->language->get('help_total');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_total'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['user_token'] . '&type=total', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/total/payment_handling', 'token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/total/payment_handling', 'token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['user_token'] . '&type=total', true);

        if (isset($this->request->post['total_payment_handling_total'])) {
            $data['total_payment_handling_total'] = $this->request->post['total_payment_handling_total'];
        } else {
            $data['total_payment_handling_total'] = $this->config->get('total_payment_handling_total');
        }

        $payments = $this->getInstalledPayments();
        foreach ($payments as $index => $payment) {
            $rateKey = "total_payment_handling_{$payment['code']}_rate";
            $flatKey = "total_payment_handling_{$payment['code']}_flat";
            $payments[$index]['rate_name'] = $rateKey;
            $payments[$index]['flat_name'] = $flatKey;
            if (isset($this->request->post[$rateKey])) {
                $payments[$index]['rate_value'] = $this->request->post[$rateKey];
            } else {
                $payments[$index]['rate_value'] = $this->config->get($rateKey);
            }
            if (isset($this->request->post[$flatKey])) {
                $payments[$index]['flat_value'] = $this->request->post[$flatKey];
            } else {
                $payments[$index]['flat_value'] = $this->config->get($flatKey);
            }
        }
        $data['payments'] = $payments;

        if (isset($this->request->post['total_payment_handling_status'])) {
            $data['total_payment_handling_status'] = $this->request->post['total_payment_handling_status'];
        } else {
            $data['total_payment_handling_status'] = $this->config->get('total_payment_handling_status');
        }

        if (isset($this->request->post['total_payment_handling_sort_order'])) {
            $data['total_payment_handling_sort_order'] = $this->request->post['total_payment_handling_sort_order'];
        } else {
            $data['total_payment_handling_sort_order'] = $this->config->get('total_payment_handling_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/total/payment_handling', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/total/payment_handling')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }

    private function getInstalledPayments()
    {
        $this->load->model('setting/extension');
        $extensions = $this->model_setting_extension->getInstalled('payment');
        foreach ($extensions as $key => $value) {
            if (!is_file(DIR_APPLICATION . 'controller/extension/payment/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/payment/' . $value . '.php')) {
                $this->model_extension_extension->uninstall('payment', $value);

                unset($extensions[$key]);
            }
        }

        $payments = array();
        $files = glob(DIR_APPLICATION . 'controller/{extension/payment,payment}/*.php', GLOB_BRACE);
        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');
                $status = $this->config->get('payment_' . $extension . '_status');
                if (in_array($extension, $extensions) && $status) {
                    $this->load->language('extension/payment/' . $extension);

                    $payments[] = array(
                        'title' => $this->language->get('heading_title'),
                        'code' => $extension
                    );
                }
            }
        }
        return $payments;
    }
}