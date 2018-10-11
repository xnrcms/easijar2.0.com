<?php

class ControllerToolTrackingImport extends Controller
{
	private $error = array();

    public function index()
    {
        $this->load->language('tool/tracking_import');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('tool/tracking_import');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            // Sanitize the filename
            $filename = 'tracking_' . time();//basename(html_entity_decode(iconv('utf-8', 'gbk//IGNORE', $this->request->files['csvfile']['name']), ENT_QUOTES, 'UTF-8'));

            move_uploaded_file($this->request->files['csvfile']['tmp_name'], DIR_STORAGE . 'upload/' . $filename);
            $file = DIR_STORAGE . 'upload/' . $filename;

			$result = $this->model_tool_tracking_import->importTracking($file);

			if ($result === true) {
			    $this->session->data['success'] = $this->language->get('text_success');
            } else {
			    $this->error['warning'] = $result;
            }
        }

		$this->getForm();
    }

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'tool/tracking_import')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

        if (empty($this->request->files['csvfile']['name']) || !is_file($this->request->files['csvfile']['tmp_name'])) {
            $this->error['csvfile'] = $this->language->get('error_csvfile');
        }

        $post_max_size = $this->return_bytes(ini_get('post_max_size'));
        $upload_max_filesize = $this->return_bytes(ini_get('upload_max_filesize'));

        if ($this->request->files['csvfile']['size'] > $post_max_size) {
            $this->error['csvfile'] = sprintf($this->language->get('error_post_max_size'), ini_get('post_max_size'));
        }

        if ($this->request->files['csvfile']['size'] > $upload_max_filesize) {
           $this->error['csvfile'] = sprintf($this->language->get('error_upload_max_filesize'), ini_get('upload_max_filesize'));
        }

        // Sanitize the filename
        $filename = basename(html_entity_decode($this->request->files['csvfile']['name'], ENT_QUOTES, 'UTF-8'));

        // Allowed file extension typesx
        if (strtolower(substr(strrchr($filename, '.'), 1)) != 'csv') {
            $this->error['csvfile'] = $this->language->get('error_filetype');
        }

		return !$this->error;
	}

    private function return_bytes($val)
    {
        $val = trim($val);

        switch (strtolower(substr($val, -1))) {
            case 'm': $val = (int)substr($val, 0, -1) * 1048576; break;
            case 'k': $val = (int)substr($val, 0, -1) * 1024; break;
            case 'g': $val = (int)substr($val, 0, -1) * 1073741824; break;
            case 'b':
                switch (strtolower(substr($val, -2, 1))) {
                    case 'm': $val = (int)substr($val, 0, -2) * 1048576; break;
                    case 'k': $val = (int)substr($val, 0, -2) * 1024; break;
                    case 'g': $val = (int)substr($val, 0, -2) * 1073741824; break;
                    default : break;
                } break;
            default: break;
        }
        return $val;
    }

	protected function getForm() {
		$data['text_form'] = $this->language->get('heading_title');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['csvfile'])) {
			$data['error_csvfile'] = $this->error['csvfile'];
		} else {
			$data['error_csvfile'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('tool/tracking_import', 'user_token=' . $this->session->data['user_token'])
		);

        $data['action'] = $this->url->link('tool/tracking_import', 'user_token=' . $this->session->data['user_token']);

		if (isset($this->request->post['csvfile'])) {
			$data['csvfile'] = $this->request->post['csvfile'];
		} else {
			$data['sort_order'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('tool/tracking_import', $data));
	}
}
