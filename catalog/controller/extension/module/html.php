<?php
class ControllerExtensionModuleHTML extends Controller {
    public function index($setting) {
        if (!$html = array_get($setting, 'module_description.' . current_language_id())) {
            return;
        }

        $heading_title = array_get($html, 'title');
        $html = array_get($html, 'description');

        if ($html) {
            $data['heading_title'] = html_entity_decode($heading_title, ENT_QUOTES, 'UTF-8');
            $data['html'] = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
            return $this->load->view('extension/module/html', $data);
        }
    }
}
