<?php
class ControllerMailForgotten extends Controller {
	public function index(&$route, &$args, &$output) {
		if ($args[0] && $args[1]) {
			$this->load->language('mail/forgotten');

			$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));

			$data['reset'] = str_replace('&amp;', '&', $this->url->link('common/reset', 'email=' . urlencode($args[0]) . '&code=' . $args[1]));
			$data['ip'] = $this->request->server['REMOTE_ADDR'];
			$data['store_url'] = HTTP_SERVER;
			$data['store'] = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');
			$data['logo'] = HTTP_SERVER  . 'view/image/logo.png';

            if (!$args[0]) {
                return;
            }
			$mail = new Mail();

			$mail->setTo($args[0]);
			$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'));
			$mail->setHtml($this->load->view('mail/forgotten', $data));
			$mail->send();
		}
	}
}
