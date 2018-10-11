<?php
class ControllerCommonHeader extends Controller {
	public function index() {
        $data = $this->getEditorData();
		$data['base'] = HTTP_SERVER;
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();
		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts();
		$data['lang'] = $this->language->get('code');
		$data['direction'] = $this->language->get('direction');

		$this->load->language('common/header');
		
		$data['text_logged'] = sprintf($this->language->get('text_logged'), $this->user->getUserName());

		if (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token'])) {
			$data['logged'] = '';

			$data['home'] = $this->url->link('common/login');
		} else {
			$data['logged'] = true;

			$data['home'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']);
			$data['logout'] = $this->url->link('common/logout', 'user_token=' . $this->session->data['user_token']);
			$data['profile'] = $this->url->link('common/profile', 'user_token=' . $this->session->data['user_token']);

			$this->load->model('tool/image');

			$data['fullname'] = '';
			$data['user_group'] = '';
			$data['image'] = $this->model_tool_image->resize('profile.png', 45, 45);
						
			$this->load->model('user/user');
	
			$user_info = $this->model_user_user->getUser($this->user->getId());
	
			if ($user_info) {
				$data['fullname'] = $user_info['fullname'];
				$data['username']  = $user_info['username'];
				$data['user_group'] = $user_info['user_group'];
	
				if (is_file(DIR_IMAGE . $user_info['image'])) {
					$data['image'] = $this->model_tool_image->resize($user_info['image'], 45, 45);
				}
			} 		
			
			// Online Stores
			$data['stores'] = array();

			$data['stores'][] = array(
				'name' => $this->config->get('config_name'),
				'href' => HTTP_CATALOG
			);

			$this->load->model('setting/store');

			$results = $this->model_setting_store->getStores();

			foreach ($results as $result) {
				$data['stores'][] = array(
					'name' => $result['name'],
					'href' => $result['url']
				);
			}
		}

		return $this->load->view('common/header', $data);
	}

	protected function getEditorData()
    {
        // init data for image water
        session_start();
        $_SESSION['image_base_path'] = DIR_IMAGE;
        $water_font_color = $this->config->get('module_water_font_color');
        if($water_font_color){
            preg_match_all('/\d+,\s*\d+,\s*\d+/', $water_font_color, $match_color);
            $water_font_color = preg_replace('/\s*/', '', $match_color[0][0]);
        }else{
            $water_font_color = '0,0,0';
        }
        $_SESSION['water_data'] = array(
            'type' => $this->config->get('module_water_type'),
            'pos' => $this->config->get('module_water_position'),
            'font' => $this->config->get('module_water_font'),
            'font_src' => DIR_SYSTEM . 'storage/font/JTBZ.TTF',
            'img' => $this->config->get('module_water_image'),
            'alpha' => (int)$this->config->get('module_water_alpha'),
            'font_color' => $water_font_color,
            'status' => $this->config->get('module_water_status')
        );

        //session for editor and image upload
        $_SESSION['image_root_path'] = HTTP_CATALOG;
        $_SESSION['folder_language'] = $this->config->get('config_admin_language');
        $_SESSION['image_upload_permission'] = $this->user->hasPermission('modify', 'common/filemanager');
        $_SESSION['system_windows'] = strstr(PHP_OS, 'WIN') ? 1 : 0;
        $_SESSION['dir_cache'] = DIR_CACHE;

        $this->load->model('tool/image');
        $result['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        $result['editor_language'] = $this->config->get('config_admin_language') == 'en-gb' ? 'en' : 'zh_CN';
        $result['title'] = $this->document->getTitle();
        setcookie('folder_language', $this->config->get('config_admin_language'), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
        return $result;
    }
}
