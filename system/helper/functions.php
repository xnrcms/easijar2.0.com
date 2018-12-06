<?php
/**
 * functions.php
 *
 * @copyright  2017 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2/23/17 10:32
 * @modified   2/23/17 10:32
 */

// OpenCart version type check
if (!function_exists('is_pro')) {
    /**
     * @return bool
     */
    function is_pro()
    {
        return (defined('OCTYPE') && strtoupper(OCTYPE) == 'PRO');
    }
}

if (!function_exists('is_free')) {
    /**
     * @return bool
     */
    function is_free()
    {
        return (defined('OCTYPE') && strtoupper(OCTYPE) == 'FREE');
    }
}


if (!function_exists('is_free_or_pro')) {

    function is_free_or_pro()
    {
        return is_pro() || is_free();
    }
}

if (!function_exists('is_ft')) {
    /**
     * @return bool
     * @throws Exception
     */
    function is_ft()
    {
        if (is_pro() && defined('FT')) {
            return FT;
        }
        return is_pro() && config('ft');
    }
}

if (!function_exists('is_std')) {
    /**
     * @return bool
     */
    function is_std()
    {
        return !defined('OCTYPE');
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check current is admin or catalog
     *
     * @return bool
     */
    function is_admin()
    {
        return defined('HTTPS_CATALOG');
    }
}

if (!function_exists('is_installer')) {
    /**
     * Check current is installer
     *
     * @return bool
     */
    function is_installer()
    {
        return defined('HTTP_OPENCART');
    }
}

if (!function_exists('is_home_page')) {
    /**
     * Check current route is common/home
     *
     * @return bool
     */
    function is_home_page()
    {
        return current_route() == 'common/home';
    }
}

if (!function_exists('is_debug')) {
    /**
     * Check current env is debug or not
     *
     * @return string
     */
    function is_debug()
    {
        return defined('DEBUG') && DEBUG;
    }
}


if (!function_exists('d')) {
    /**
     * Dump the passed variables and end the script.
     *
     * @param  mixed
     * @return void
     */
    function d(...$args)
    {
        foreach ($args as $x) {
            (new \Illuminate\Support\Debug\Dumper())->dump($x);
        }
    }
}

if (!function_exists('sub_string')) {
    /**
     * @param $string
     * @param $length
     * @param string $dot
     * @return string
     */
    function sub_string($string, $length, $dot = '...')
    {
        $strLength = strlen($string);
        if ($length <= 0) {
            return $string;
        } elseif ($strLength <= $length) {
            return $string;
        }
        return utf8_substr($string, 0, $length) . $dot;
    }
}

if (!function_exists('format_date')) {
    /**
     * @param $format
     * @param null $timestamp
     * @return mixed
     */
    function format_date($format, $timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        $datetime = new Utils\Datetime();
        $baseDatetime = date('Y-m-d H:i:s', $timestamp);
        $datetime = $datetime->convert($baseDatetime, '', $format);
        return $datetime;
    }
}

if (!function_exists('render_csv')) {
    /**
     * Convert a value to studly caps case.
     *
     * @param  string $value
     * @return string
     */
    function render_csv($value)
    {
        $filename = date('Ymd') . '.csv';
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $value;
        exit;
    }
}


if (!function_exists('registry')) {
    /**
     * Get Registry Instance
     *
     * @param null $type
     * @return mixed|Registry
     * @throws Exception
     */
    function registry($type = null)
    {
        $availableTypes = ['config', 'session', 'document', 'url', 'load', 'currency', 'language', 'request', 'log', 'customer'];
        if ($type && !in_array($type, $availableTypes)) {
            throw new \Exception("Invalid registry type {$type}");
        }
        if ($type) {
            return Registry::getSingleton()->get($type);
        }
        return Registry::getSingleton();
    }
}

if (!function_exists('config')) {
    /**
     * Get config values through keys
     *
     * @param string $key
     * @return mixed|null
     * @throws Exception
     */
    function config($key = '', $default = null)
    {
        if (empty($key)) {
            return $default;
        }
        $segments = explode('.', $key);
        $value = registry('config')->get($segments[0]);
        if (is_null($value)) {
            return $default;
        }
        if (count($segments) == 1) {
            return is_null($value) ? $default : $value;
        }
        array_shift($segments);
        return array_get($value, implode('.', $segments), $default);
    }
}

if (!function_exists('model')) {
    /**
     * Get model object through route
     *
     * @param string $route
     * @return mixed
     * @throws Exception
     */
    function model($route = '')
    {
        $registry = registry();
        $registry->get('load')->model($route);
        $route = preg_replace('/[^a-zA-Z0-9_\/]/', '', (string)$route);

        $modelName = 'model_' . str_replace('/', '_', $route);
        if ($registry->has($modelName)) {
            return $registry->get($modelName);
        }
        return null;
    }
}

if (!function_exists('session')) {
    /**
     * Get Session Instance
     *
     * @return Session
     * @throws Exception
     */
    function session()
    {
        return registry('session');
    }
}

if (!function_exists('document')) {
    /**
     * Get Document instance
     *
     * @return Document
     * @throws Exception
     */
    function document()
    {
        return registry('document');
    }
}

if (!function_exists('request')) {
    /**
     * Get Request instance
     *
     * @return Request
     * @throws Exception
     */
    function request()
    {
        return registry('request');
    }
}

if (!function_exists('url')) {
    /**
     * Get Url instance
     *
     * @return Url
     * @throws Exception
     */
    function url()
    {
        return registry('url');
    }
}

if (!function_exists('currency')) {
    /**
     * Get Currency instance
     *
     * @return mixed|\Cart\Currency
     * @throws Exception
     */
    function currency()
    {
        return registry('currency');
    }
}

if (!function_exists('customer')) {
    function customer()
    {
        return registry('customer');
    }
}

if (!function_exists('t')) {
    /**
     * Get Translation text
     *
     * @param $key
     * @return mixed
     * @throws Exception
     */
    function t($key)
    {
        return registry('language')->get($key);
    }
}

if (!function_exists('debug_bar')) {
    /**
     * Get DebugBar Renderer
     *
     * @return \DebugBar\JavascriptRenderer
     * @throws Exception
     */
    function debug_bar()
    {
        return registry()->get('debug_bar');
    }
}

if (!function_exists('current_route')) {
    function current_route()
    {
        return array_get(request()->get, 'route', 'common/home');
    }
}

if (!function_exists('current_language_id')) {
    /**
     * Get current language code
     *
     * @return string
     * @throws Exception
     */
    function current_language_id()
    {
        return config('config_language_id');
    }
}

if (!function_exists('current_language_code')) {
    /**
     * Get current language code
     *
     * @return string
     * @throws Exception
     */
    function current_language_code()
    {
        return strtolower(array_get(session()->data, 'language'));
    }
}

if (!function_exists('is_zh_cn')) {
    /**
     * Check if the language is zh_cn
     *
     * @throws Exception
     */
    function is_zh_cn()
    {
        return current_language_code() == 'zh-cn';
    }
}

if (!function_exists('image_resize')) {
    /**
     * @param string $image
     * @param int $width
     * @param int $height
     * @return mixed
     * @throws Exception
     */
    function image_resize($image = 'placeholder.png', $width = 100, $height = 100)
    {
        if (starts_with($image, 'https://') || starts_with($image, 'http://')) {
            return $image;
        }
        registry('load')->model('tool/image');
        return registry()->get('model_tool_image')->resize($image, $width, $height);
    }
}

if (!function_exists('image_exists')) {
    function image_exists($image)
    {
        return is_file(DIR_IMAGE . $image) ||
            is_file(DIR_OCROOT . 'extension/theme/' . config('config_theme') . '/image/' . $image);
    }
}

if (!function_exists('image_original_url')) {
    function image_original_url($image)
    {
        $extension_image = 'extension/image/' . $image;
        if (is_file(DIR_OCROOT . $extension_image)) {
            return url()->getBaseUrl() . $extension_image;
        }
        return url()->imageLink($image);
    }
}

function resource_url($uri)
{
    $resource = 'extension/' . $uri;
    if (is_file(DIR_OCROOT . $resource)) {
        return url()->getBaseUrl() . $resource;
    }
    return url()->getBaseUrl() . $uri;
}

if (!function_exists('template_exists')) {
    /**
     * Check if twig file exists
     */
    function template_exists($route)
    {
        $theme = config('config_theme') == 'default' ? config('theme_default_directory') : config('config_theme');
        return is_file(DIR_TEMPLATE . "{$theme}/template/{$route}.twig") ||
            is_file(DIR_TEMPLATE . "default/template/{$route}.twig");
    }
}

if (!function_exists('get_firstname')) {
    /**
     * Parse first name based on full name.
     *
     * @param string $fullName
     * @return bool|string
     */
    function get_firstname($fullName = '')
    {
        $arrFullName = explode(' ', $fullName);
        $firstName = isset($arrFullName[0]) ? isset($arrFullName[0]) : '';
        return $firstName;
    }
}

if (!function_exists('get_lastname')) {
    /**
     * Parse last name based on full name.
     *
     * @param string $fullName
     * @return bool|string
     */
    function get_lastname($fullName = '')
    {
        $arrFullName = explode(' ', $fullName);
        $lastName = isset($arrFullName[1]) ? isset($arrFullName[1]) : '';
        return $lastName;
    }
}

if (!function_exists('base_url')) {
    /**
     * Get base url.
     *
     * @return bool|string
     */
    function base_url()
    {
        if (defined('HTTPS_CATALOG')) {
            return HTTPS_CATALOG;
        }
        return HTTPS_SERVER;
    }
}

if (!function_exists('address_format')) {
    /**
     * Format address.
     *
     * @param array $address
     * @param string $format
     * @param string $prefix
     * @return mixed
     */
    function address_format($address = array(), $format = '', $prefix = '', $html = true)
    {
        if (!$format) {
            if (is_pro() || is_free()) {
                $format = '{fullname} ({telephone})' . "\n" . '{zone}{city}{county}{address_1} {company} {postcode}';
            } else {
                $format = '{fullname} ({telephone})' . "\n" . '{country}{zone}{city}{county}{address_1} {company} {postcode}';
            }
        }

        if ($prefix) {
            $prefix .= '_';
        }

        $find = array(
            '{fullname}',
            '{telephone}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{county}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}',
        );

        $replace = array(
            'fullname'  => isset($address[$prefix . 'fullname']) ? $address[$prefix . 'fullname'] : '',
            'telephone' => isset($address[$prefix . 'telephone']) ? $address[$prefix . 'telephone'] : '',
            'company'   => isset($address[$prefix . 'company']) ? $address[$prefix . 'company'] : '',
            'address_1' => isset($address[$prefix . 'address_1']) ? $address[$prefix . 'address_1'] : '',
            'address_2' => isset($address[$prefix . 'address_2']) ? $address[$prefix . 'address_2'] : '',
            'county'    => isset($address[$prefix . 'county']) ? $address[$prefix . 'county'] : '',
            'city'      => isset($address[$prefix . 'city']) ? $address[$prefix . 'city'] : '',
            'postcode'  => isset($address[$prefix . 'postcode']) ? $address[$prefix . 'postcode'] : '',
            'zone'      => isset($address[$prefix . 'zone']) ? $address[$prefix . 'zone'] : '',
            'zone_code' => isset($address[$prefix . 'zone_code']) ? $address[$prefix . 'zone_code'] : '',
            'country'   => isset($address[$prefix . 'country']) ? $address[$prefix . 'country'] : ''
        );

        $strAddress = trim(str_replace($find, $replace, $format));
        if ($html) {
            $strAddress = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', $strAddress));
        }
        return $strAddress;
    }
}

if (!function_exists('old')) {
    function old($key, $default = null)
    {
        return request()->post ? array_get(request()->post, $key, $default) : $default;
    }
}

if (!function_exists('parse_filters')) {
    /**
     * @param $filter_params
     * @return array
     */
    function parse_filters($filter_params)
    {
        $filters = explode('|', $filter_params);
        return array_filter($filters, function ($filter) {
            return (int)($filter) > 0;
        });
    }
}

if (!function_exists('parse_attributes')) {
    /**
     * @param $filter_params
     * @return array
     */
    function parse_attributes($filter_params)
    {
        $filters = parse_filters($filter_params);
        $attributes = array();
        foreach ($filters as $filter) {
            $item = explode(':', $filter);
            if (count($item) == 2) {
                $attributes[$item[0]][] = $item[1];
            }
        }
        return $attributes;
    }
}

if (!function_exists('template')) {
    function template($route)
    {
        if (config('config_theme') == 'default') {
            if (is_file(DIR_TEMPLATE . config('theme_default_directory') . "/template/{$route}.twig")) {
                return config('theme_default_directory') . "/template/{$route}.twig";
            }
        }

        $template = 'extension/theme/' . config('config_theme') . "/catalog/view/theme/" . config('config_theme') . "/template/{$route}.twig";
        if (is_file(DIR_OCROOT . $template)) {
            return config('config_theme') . "/template/{$route}.twig";
        }
        if (is_file(DIR_TEMPLATE . config('config_theme') . "/template/{$route}.twig")) {
            return config('config_theme') . "/template/{$route}.twig";
        }
        return "default/template/{$route}.twig";
    }
}

if (!function_exists('get_calling_codes')) {
    /**
     * @return array
     */
    function get_calling_codes()
    {
        $calling_codes = array();

        $countries = countries(true);
        foreach ($countries as $key=>$value) {
            $country = country($key);
            if ($country->get('dialling.calling_code', []) === null) {
                continue;
            }
            $calling_code = $country->getCallingCode();
            $name = $country->getNativeName('zho');
            if ($calling_code && (!defined('CALLING_CODES') || in_array($calling_code, explode(',', CALLING_CODES)))) {
                
                //删除不需要的区号
                if (!in_array($calling_code, [86,65,60,63])) continue;
                if ($calling_code == 65) {
                    $name = 'Singapore';
                }

                $calling_codes[] = array(
                    'name'           => $name.'(+'.$calling_code.')',
                    'code'           => $calling_code
                );
            }
        }

        return $calling_codes;
    }
}
if (!function_exists('format_find_field'))
{
    function format_find_field($filed = '',$prefix = '')
    {
        $prefix     = !empty($prefix) ? $prefix . '.' : '';
        if (empty($filed))  return $prefix . '*';

        $filed  = is_array($filed) ? $filed : explode(',', $filed);

        $f      = [];
        foreach ($filed as $value) {
            $ff     = explode('AS', $value);
            $ff1    = (isset($ff[0]) && !empty($ff[0])) ? trim($ff[0]) : '';
            $ff2    = (isset($ff[1]) && !empty($ff[1])) ? ' AS ' . $ff[1] : '';

            if(empty($ff1)) continue;
            
            $f[]    = $prefix . '`'.$ff1.'`' . $ff2;
        }

        return implode(',', $f);
    }
}

if (!function_exists('wr'))
{
    //文件写入，快捷调试
    function wr($data,$file = 'info.txt',$return=true)
    {
        $return == true ? file_put_contents(DIR_SYSTEM.$file,var_export($data,true),FILE_APPEND) : file_put_contents('./system/'.$file,var_export($data,true));
    }
}

if (!function_exists('curl_http')) 
{
    /**
     * Curl请求
     * @param number $url 请求的URL
     * @param number $body 字符串类型
     * @return string
     */
    function curl_http($url,$body='',$method='DELETE',$headers=array())
    {
        $httpinfo       = [];
        $ci             = curl_init();

        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ci, CURLOPT_FAILONERROR, false);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        }

        /* Curl settings */
        curl_setopt($ci,CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_0);
        curl_setopt($ci,CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($ci,CURLOPT_TIMEOUT,30);
        curl_setopt($ci,CURLOPT_ENCODING,'');
        curl_setopt($ci,CURLOPT_HEADER,false);

        switch($method){
            case 'POST':
                curl_setopt($ci,CURLOPT_POST,TRUE);
                if(!empty($body)){
                    curl_setopt($ci,CURLOPT_POSTFIELDS,http_build_query($body));
                }
                break;
            case 'DELETE':
                if(!empty($body)){
                    $url=$url.'?'.str_replace('amp;', '', http_build_query($body));
                }
        }

        $response   = curl_exec($ci);

        curl_close($ci);

        return $response;
    }
}

if (!function_exists('random_string')) 
{
    /**
     * 生成随机数
     * @param number $length 字符串长度
     * @param number $type 字符串类型
     * @return string
     */
    function random_string($length, $type = 0) {
        $arr  = [
            0 => '123456789',
            1 => 'abcdefghjkmnpqrstuxy',
            2 => 'ABCDEFGHJKMNPQRSTUXY',
            3 => '123456789abcdefghjkmnpqrstuxy',
            4 => '123456789ABCDEFGHJKMNPQRSTUXY',
            5 => 'abcdefghjkmnpqrstuxyABCDEFGHJKMNPQRSTUXY',
            6 => '123456789abcdefghjkmnpqrstuxyABCDEFGHJKMNPQRSTUXY',
            7 => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];
        
        $chars = $arr[$type] ? $arr[$type] : $arr[7];
        $hash  = '';
        $max   = strlen($chars) - 1;
        for($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        
        return $hash;
    }
}

if (!function_exists('get_country_code')) 
{
    function get_country_code($code = 0,$type=0) {
        $arr    = ['86'=>'44','60'=>'129','63'=>'168','65'=>'188'];
        if ($type == 1) {
            $arr    = array_flip($arr);
        }
        return isset($arr[$code]) ? $arr[$code] : 0;
    }
}

if (!function_exists('get_order_type')) 
{
    function get_order_type($order_sn = '') {
        return !empty($order_sn) ? intval(substr($order_sn, 14,1)) : 0;
    }
}

if (!function_exists('get_tabname')) 
{
    function get_tabname($tabname = '',$prefix = '') {
        $prefix         = !empty($prefix) ? $prefix : DB_PREFIX;
        return !empty($tabname) ? "`" . $prefix . $tabname ."`" : 0;
    }
}

if (!function_exists('is_telephone')) 
{
    function is_telephone($mobile)
    {
        return (preg_match('/^1(3[0-9]|4[0-9]|5[0-35-9]|7[0-9]|8[0-9])\\d{8}$/', $mobile)) ? true : false;
    }
}

if (!function_exists('is_idcard')) 
{
    function is_idcard( $id )
    {
      $id = strtoupper($id);
      $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
      $arr_split = array();
      if(!preg_match($regx, $id))
      {
        return false;
      }
      if(15==strlen($id)) //检查15位
      {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";
        @preg_match($regx, $id, $arr_split);
        //检查生日日期是否正确
        $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        if(!strtotime($dtm_birth))
        {
          return false;
        }
        else
        {
          return true;
        }
      }
      else //检查18位
      {
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        if(!strtotime($dtm_birth)) //检查生日日期是否正确
        {
          return false;
        }
        else
        {
          //检验18位身份证的校验码是否正确。
          //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
          $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
          $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
          $sign = 0;
          for ( $i = 0; $i < 17; $i++ )
          {
            $b = (int) $id{$i};
            $w = $arr_int[$i];
            $sign += $b * $w;
          }
          $n = $sign % 11;
          $val_num = $arr_ch[$n];
          if ($val_num != substr($id,17, 1))
          {
            return false;
          }
          else
          {
            return true;
          }
        }
      }
    }
}

if (!function_exists('is_json'))
{
    /**
     * [is_json 判断是否是json格式数据]
     * @param  [type]  $string [json字符串]
     * @return boolean
     */
    function is_json($string) {
        
         json_decode($string);
         
         return (json_last_error() == JSON_ERROR_NONE);
    }
}