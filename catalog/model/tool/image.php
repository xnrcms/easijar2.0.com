<?php
class ModelToolImage extends Model {
    public function resize($filename, $width = 100, $height = 100, $placeholder = true) {
        if (!image_exists($filename)) {
            if ($placeholder) {
                $filename = 'placeholder.png';
            } else {
                return;
            }
        }

        if ($filename == 'placeholder.png' && is_file(DIR_IMAGE . 'placeholder/placeholder.png')) {
            $filename = 'placeholder/' . $filename;
            $image_old = DIR_IMAGE . $filename;
        } else {
            $extension_image = DIR_OCROOT . 'extension/image/' . $filename;
            if (is_file($extension_image)) {
                $image_old = $extension_image;
            } else {
                $image_old = DIR_IMAGE . $filename;
            }
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

        if (!is_file(DIR_IMAGE . $image_new) || (filemtime($image_old) > filemtime(DIR_IMAGE . $image_new))) {
            list($width_orig, $height_orig, $image_type) = getimagesize($image_old);

            if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
                return $image_old;
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
                $image = new Image($image_old);
                $image->resize($width, $height);
                $image->save(DIR_IMAGE . $image_new);
            } else {
                copy($image_old, DIR_IMAGE . $image_new);
            }
        }

        $image_new = str_replace(' ', '%20', $image_new);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

        return $this->url->imageLink($image_new);
    }

    /**
     * Get remote image and save to local storage.
     *
     * @param $url
     * @param string $save_dir
     * @param string $filename
     * @param int $type
     * @return array
     */
    function getImage($url, $save_dir = '', $filename = '', $type = 1)
    {
        if (trim($url) == '') {
            return array('file_name' => '', 'save_path' => '', 'error' => 1);
        }
        if (trim($save_dir) == '') {
            $save_dir = './';
        }
        if (trim($filename) == '') {//保存文件名
            $ext = strrchr($url, '.');
            if ($ext != '.gif' && $ext != '.jpg') {
                return array('file_name' => '', 'save_path' => '', 'error' => 3);
            }
            $filename = time() . $ext;
        }
        if (0 !== strrpos($save_dir, '/')) {
            $save_dir .= '/';
        }
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return array('file_name' => '', 'save_path' => '', 'error' => 5);
        }
        //获取远程文件所采用的方法
        if ($type) {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $img = curl_exec($ch);
            curl_close($ch);
        } else {
            ob_start();
            readfile($url);
            $img = ob_get_contents();
            ob_end_clean();
        }
        //文件大小
        if (is_file($save_dir . $filename)) {
            unlink($save_dir . $filename);
        }
        $fp2 = @fopen($save_dir . $filename, 'a');
        fwrite($fp2, $img);
        fclose($fp2);
        unset($img, $url);
        return array('file_name' => $filename, 'save_path' => $save_dir . $filename, 'error' => 0);
    }
}
