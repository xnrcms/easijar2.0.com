<?php
class ControllerExtensionModuleAvatar extends Controller {
	public function index() {
        // Avatar upload only works on route starts with account/%
	    if (!config('module_avatar_status') || !starts_with(current_route(), 'account/')) {
	        return;
        }

		$this->load->language('extension/module/avatar');

        $this->document->addScript('catalog/view/javascript/jquery/photoclip/iscroll-zoom-min.js');
        $this->document->addScript('catalog/view/javascript/jquery/photoclip/hammer.min.js');
        $this->document->addScript('catalog/view/javascript/jquery/photoclip/lrz.all.bundle.js');
        $this->document->addScript('catalog/view/javascript/jquery/photoclip/PhotoClip.js');

		return $this->load->view('extension/module/avatar');
	}
}
