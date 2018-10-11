<?php

class ControllerToolGdExportImport extends Controller
{
	private $error = array();

    public function upload()
    {
        $this->load->language('tool/gd_export_import');

        $json = array();

        // Check user has permission
        if (!$this->user->hasPermission('modify', 'tool/gd_export_import')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (empty($this->request->files['upload']['name']) || !is_file($this->request->files['upload']['tmp_name'])) {
            $json['error'] = $this->language->get('error_upload');
        }

        $post_max_size = $this->return_bytes(ini_get('post_max_size'));
        $upload_max_filesize = $this->return_bytes(ini_get('upload_max_filesize'));

        if ($this->request->files['upload']['size'] > $post_max_size) {
            $json['error'] = sprintf($this->language->get('error_post_max_size'), ini_get('post_max_size'));
        }

        if ($this->request->files['upload']['size'] > $upload_max_filesize) {
            $json['error'] = sprintf($this->language->get('error_upload_max_filesize'), ini_get('upload_max_filesize'));
        }

        if (!$json) {
            // Sanitize the filename
            $filename = basename(html_entity_decode($this->request->files['upload']['name'], ENT_QUOTES, 'UTF-8'));

            if ((utf8_strlen($filename) < 3) || (utf8_strlen($filename) > 128)) {
                $json['error'] = $this->language->get('error_filename');
            }

            // Allowed file extension typesx
            if (strtolower(substr(strrchr($filename, '.'), 1)) != 'xlsx') {
                $json['error'] = $this->language->get('error_filetype');
            }
        }

        if (!$json) {
            move_uploaded_file($this->request->files['upload']['tmp_name'], DIR_STORAGE . 'upload/' . $filename);
            $file = DIR_STORAGE . 'upload/' . $filename;

            $this->load->model('tool/gd_export_import');
            if ($this->request->get['type'] == 'base' || $this->request->get['type'] == 'product') {
                $result = $this->model_tool_gd_export_import->upload($file, $this->request->get['type']);
            } else {
                $json['error'] = "Type Error!";
            }

            if ($result === true) {
                $json['success'] = $this->language->get('text_success');
            } else {
                $json['error'] = $result;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
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

    public function download()
    {
        $this->load->language('tool/gd_export_import');

        if ($this->validateForm()) {
            $this->load->model('tool/gd_export_import');
            $this->model_tool_gd_export_import->download($this->request->get['type'], $this->request->post);
            return;
       }

       $this->index();
    }

	protected function validateForm() {
		if ($this->request->get['type'] == 'product') {
		    if ($this->request->post['exportway'] == 'pid') {
		        if (!$this->request->post['min']) {
                    $this->error['min'] = $this->language->get('error_min_pid');
                }
		        if (!$this->request->post['max']) {
                    $this->error['max'] = $this->language->get('error_max_pid');
                }
		        if ($this->request->post['min'] && $this->request->post['max'] && $this->request->post['min'] > $this->request->post['max']) {
                    $this->error['warning'] = $this->language->get('error_min_max');
                }
            } else if ($this->request->post['exportway'] == 'page') {
		        if (!$this->request->post['min']) {
                    $this->error['min'] = $this->language->get('error_min_page');
                }
		        if (!$this->request->post['max']) {
                    $this->error['max'] = $this->language->get('error_max_page');
                }
            } else {
                $this->error['warning'] = $this->language->get('error_exportway');
            }
		}

		return !$this->error;
	}

    public function index()
    {
        $this->load->language('tool/gd_export_import');

        $this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['min'])) {
			$data['error_min'] = $this->error['min'];
		} else {
			$data['error_min'] = array();
		}

		if (isset($this->error['max'])) {
			$data['error_max'] = $this->error['max'];
		} else {
			$data['error_max'] = array();
		}

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('tool/gd_export_import', 'user_token=' . $this->session->data['user_token'])
        );

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['warning'])) {
            $data['error_warning'] = $this->session->data['warning'];
            unset($this->session->data['warning']);
        }

		if (isset($this->request->post['exportway'])) {
			$data['exportway'] = $this->request->post['exportway'];
		} else {
			$data['exportway'] = 'pid';
		}

		if (isset($this->request->post['min'])) {
			$data['min'] = $this->request->post['min'];
		} else {
			$data['min'] = '';
		}

		if (isset($this->request->post['max'])) {
			$data['max'] = $this->request->post['max'];
		} else {
			$data['max'] = '';
		}

        $data['export'] = $this->url->link('tool/gd_export_import/download',
            'type=base&user_token=' . $this->session->data['user_token']);
        $data['export_product'] = $this->url->link('tool/gd_export_import/download',
            'type=product&user_token=' . $this->session->data['user_token']);


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('tool/gd_export_import', $data));
    }
}
