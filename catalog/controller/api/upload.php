<?php
class ControllerApiUpload extends Controller{
    public function image()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('tool/upload');

        $allowKey       = ['api_token','tag'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();
        $json           = [];

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        if (!(isset($req_data['tag']) && !empty($req_data['tag'])) || !preg_match_all("/^[a-zA-Z]{1}[a-zA-Z0-9_]{1,15}$/",$req_data['tag'])) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:tag is error']));
        }

        $dirname                        = $req_data['tag'] . '/';

        if (!empty($this->request->files['files']['name']) && is_file($this->request->files['files']['tmp_name'])) {
            // Sanitize the filename
            $filename = basename(preg_replace('/[^a-zA-Z0-9\.\-\s+]/', '', html_entity_decode($this->request->files['files']['name'], ENT_QUOTES, 'UTF-8')));

            // Validate the filename length
            if ((utf8_strlen($filename) < 2) || (utf8_strlen($filename) > 64)) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filename')]));
            }

            // Allowed file extension types
            $allowed           = [];
            $extension_allowed = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_ext_allowed'));
            $filetypes         = explode("\n", $extension_allowed);

            foreach ($filetypes as $filetype) {
                $allowed[] = trim($filetype);
            }

            if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filename')]));
            }

            // Allowed file mime types
            $allowed           = [];
            $mime_allowed      = preg_replace('~\r?\n~', "\n", $this->config->get('config_file_mime_allowed'));
            $filetypes         = explode("\n", $mime_allowed);

            foreach ($filetypes as $filetype) {
                $allowed[]     = trim($filetype);
            }

            if (!in_array($this->request->files['files']['type'], $allowed)) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
            }

            // Check to see if any PHP files are trying to be uploaded
            $content           = file_get_contents($this->request->files['files']['tmp_name']);

            if (preg_match('/\<\?php/i', $content)) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_filetype')]));
            }

            // Return any upload error
            if ($this->request->files['files']['error'] != UPLOAD_ERR_OK) {
                return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_upload_' . $this->request->files['files']['error'])]));
            }
        } else {
            return $this->response->setOutput($this->returnData(['msg'=>$this->language->get('error_upload')]));
        }

        $file = $filename . '.' . token(32);

        //文件名扰码由扩展名后改为扩展名前
        $file = 'temp' . '.' . utf8_substr(mb_strrchr($filename, '.'), 1);

        if (!is_dir(DIR_IMAGE . $dirname)) @mkdir(DIR_IMAGE . $dirname, 0777);

        move_uploaded_file($this->request->files['files']['tmp_name'], DIR_IMAGE . $dirname . $file);

        $img = $this->resize($dirname . $file, 600, 600,trim($dirname,'/'));
        unlink(DIR_IMAGE . $dirname . $file);

        if ($this->request->server['HTTPS']) {
            $json['imgurl'] =  $this->config->get('config_ssl') . 'image/' . $img;
        } else {
            $json['imgurl'] =  $this->config->get('config_url') . 'image/' . $img;
        }

        $json['path']       = $img;

        //临时保存上传的图片
        $this->load->model('tool/image');
        $this->model_tool_image->save_temp_image([$img]);

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>$this->language->get('text_upload'),'data'=>$json]));
    }

    public function resize($filename, $width, $height,$dirname) 
    {
        if (!is_file(DIR_IMAGE . $filename)) return;

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $image_old = $filename;
        $image_new = $dirname . '/' . $dirname . '-image-' . time() . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

        if (!is_file(DIR_IMAGE . $image_new))
        {
            list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);
                 
            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) { 
                return DIR_IMAGE . $image_old;
            }
                        
            $path = '';

            $directories = explode('/', dirname($image_new));

            foreach ($directories as $directory) {
                $path = $path . '/' . $directory;

                if (!is_dir(DIR_IMAGE . $path)) {
                    @mkdir(DIR_IMAGE . $path, 0777);
                }
            }

            if ($width_orig != $width || $height_orig != $height) {
                $image = new Image(DIR_IMAGE . $image_old);
                $image->resize($width, $height);
                $image->save(DIR_IMAGE . $image_new);
            } else {
                copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
            }
        }
        
        $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

        return $image_new;
    }
}