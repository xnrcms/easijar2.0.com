<?php

/**
 * editor.php
 *
 * @copyright 2018 OpenCart.cn
 *
 * All Rights Reserved
 * @link http://guangdawangluo.com
 *
 * @author stiffer.chen <chenlin@opencart.cn>
 * @created 2018-06-12 15:46
 * @modified 2018-06-12 15:46
 */
namespace Seller;

class Editor
{
    private $registry = null;
    private static $instance = null;

    private function __construct($registry = null)
    {
        $this->registry = $registry;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance($registry = null)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($registry);
        }

        return self::$instance;
    }

    public function __get($key)
    {
        return $this->registry->get($key);
    }

    public function __set($key, $value)
    {
        $this->registry->set($key, $value);
    }

    public function getEditorData()
    {
        // init data for image water
        if (!session_id()) {
            session_start();
        }
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

        $_SESSION['image_root_path'] = HTTP_SERVER;
        $_SESSION['folder_language'] = $this->session->data['language'];
        $_SESSION['image_upload_permission'] = $this->imagePermission();
        $_SESSION['system_windows'] = strstr(PHP_OS, 'WIN') ? 1 : 0;
        $_SESSION['dir_cache'] = DIR_CACHE;
        $_SESSION['seller_id'] = MsSeller::getInstance($this->registry)->sellerId();

        $this->load->model('tool/image');
        $result['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        $result['editor_language'] = isset($this->session->data['language']) && $this->session->data['language'] == 'en-gb' ? 'en' : 'zh_CN';
        $result['title'] = $this->document->getTitle();
        setcookie('folder_language', $this->session->data['language'], time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
        return $result;
    }

    private function imagePermission()
    {
        return MsSeller::getInstance($this->registry)->isSeller() ? true : false;
    }
}