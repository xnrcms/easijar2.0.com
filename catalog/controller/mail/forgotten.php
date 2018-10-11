<?php
class ControllerMailForgotten extends Controller {
	public function index(&$route, &$args, &$output) {
		if ($args[0] && $args[1]) {
			if (is_numeric($args[0])) {
				$customer = \Models\Customer::find($args[0]);
				if (!$customer || !$customer->email) {
					return;
				}
				$email = $customer->email;
			} else {
				$email = $args[0];
			}

			$this->load->language('mail/forgotten');

			$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$data['text_change'] = $this->language->get('text_change');
			$data['text_ip'] = $this->language->get('text_ip');
			$data['button_reset'] = $this->language->get('button_reset');

			$data['reset'] = str_replace('&amp;', '&', $this->url->link('account/reset', 'email=' . urlencode($email) . '&code=' . $args[1]));
			$data['ip'] = $this->request->server['REMOTE_ADDR'];
			$data['store_url'] = HTTP_SERVER;
			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			if ($this->config->get('config_logo')) {
				$data['logo'] = $this->url->imageLink(config('config_logo'));
			}

            if (!$args[0]) {
                return;
            }
			$mail = new Mail();

			$mail->setTo($email);
			$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'));
			$mail->setHtml($this->load->view('mail/forgotten', $data));
			$mail->send();
		}
	}
}
