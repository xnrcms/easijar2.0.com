<?php
class ControllerCrontabHandle3 extends Controller {

	//定时任务
	public function index() 
	{
        set_time_limit(0);
        $this->load->model('catalog/handle');
        
        $req_data           = array_merge($this->request->get,$this->request->post);
        $step               = (isset($req_data['step']) && (int)$req_data['step'] > 0) ? (int)$req_data['step'] : 0;
        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 100;
        $ini_time           = time();

        $filter_data        = [
            'start'         => ($page - 1) * $limit,
            'limit'         => $limit,
            'product_id'    =>1,
            'language_id'   => 1
        ];

        if ($step == 1) 
        {
            //处理属性
            $total                          = $this->model_catalog_handle->getProductsTotals($filter_data);
            $products                       = $this->model_catalog_handle->getProducts($filter_data);
            print_r($total);exit();

            $remainder                      = intval($this->session->data['handle_data']['ototals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['ototals'] - $remainder;
            $is_save                        = $remainder > 0 ? true : false;
            $option_id                      = $option_info['option_id'];

            if ($remainder > 0) {

                if (!empty($option_info)) {
                    //根据选项名称查找属性信息
                    $variant_description    = $this->model_catalog_handle->get_variant_description($option_info['name']);
                    if (!empty($variant_description)) {
                        $variant_id         = $variant_description['variant_id'];
                    }else{
                        //新增属性 获取variant_id
                        $variant_id         = $this->model_catalog_handle->add_variant(['name'=>$option_info['name']]);
                    }

                    $this->handle_variant($variant_id,$option_id,true);
                }

                $page ++;
            }else{
                $page = 0;
                $step = 16;
            }

            $this->echo_log([
                'handle_name'=>'商品选项属性转换',
                'handle_num1'=>$this->session->data['handle_data']['ototals'],
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'属性名称：'.$option_info['name'] . '(属性ID：' . $option_info['option_id'] . ')',
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }
    }

    //1:纯英文;2:纯中文;3:中英文混合
    private function string_type($str)
    {
        $m      = mb_strlen($str,'utf-8');
        $s      = strlen($str);

        if(trim($str)=='') return 0;
        if($s==$m) return 1;
        if ($s%$m==0&&$s%3==0) return 2;

        return 3;
    }

    private function handle_product_children($info)
    {
        if (!empty($info))
        {
            //首先获取主产品信息
            $pinfo1                      = $this->model_catalog_handle->get_product($info['product_id']);
            $pinfo2                      = $this->model_catalog_handle->get_product_description($info['product_id']);
            $category                    = $this->model_catalog_handle->get_product_to_category($info['product_id']);
            $save_data1                  = $pinfo1;//商品数据
            $save_data2                  = $pinfo2;//商品详情数据
            $save_data3                  = [];//分类数据

            unset($save_data1['product_id']);
            unset($save_data1['sku']);
            unset($save_data2['language_id']);
            unset($save_data2['product_id']);

            $shop                       = [108=>1,110=>2,130=>3,112=>4,113=>5,114=>6,153=>7,184=>8];
            $seller_id                  = 0;
            if (!empty($category)) {
                foreach ($category as $key => $value) {
                    $save_data3[$value['category_id']]   = $value['category_id'];
                    if (isset($shop[$value['category_id']])) {
                        $seller_id  = $shop[$value['category_id']];
                    }
                }

                sort($save_data3);
            }

            $save_data1['seller_id']  = $seller_id;
            $this->handle_product_info($info['product_id'],$save_data1,$save_data2,$save_data3);
        }
    }

    private function handle_product_info_for_name($info)
    {
        if (!empty($info))
        {
            $updata                         = [];
            $updata['language_id']          = 2;
            $updata['product_id']           = $info['product_id'];
            $updata['name']                 = isset($info['name']) ? $this->filter_keywords($info['name']) : '';
            $updata['tag']                  = $updata['name'];
            $updata['meta_title']           = $updata['name'];
            $updata['meta_description']     = $updata['name'];
            $updata['meta_keyword']         = $updata['name'];
            $description                    = isset($info['description']) ? $info['description'] : '';
            $updata['description']          = str_replace(['http://v2.easijar.com','https://v2.easijar.com','http://10.5.151.185','https://10.5.151.185'],['..','..','..','..'], $description);

            $this->model_catalog_handle->update_product_description($updata);
            //print_r($updata);

            //翻译入库
            $updata['language_id']          = 1;
            $updata['name']                 = $this->handle_translate($updata['name']);
            $updata['tag']                  = $updata['name'];
            $updata['meta_title']           = $updata['name'];
            $updata['meta_description']     = $updata['name'];
            $updata['meta_keyword']         = $updata['name'];
            //print_r($updata);exit();
            $this->model_catalog_handle->update_product_description($updata);
        }
    }

    private function handle_translate($str)
    {
        if (empty($str) || strlen($str) <= 0)  return '';
        if ($this->string_type($str) === 1) return '';

        //return 'en-'.$str;
        //开始翻译
        $outputStr    = $this->load->controller('extension/interface/translate/translate_aliyun', ['d'=>'en','q'=>$str,'s'=>'zh-cn']);
        return !empty($outputStr) ? ucfirst($outputStr) : $str;
    }

    private function handle_product_info($product_id,$product,$product_desc,$category)
    {
        if ( (int)$product_id <= 0 || empty($product) || empty($product_desc)) return;

        //修改主产品
        $this->model_catalog_handle->update_product($product_id);
        $this->model_catalog_handle->add_ms_product_seller($product_id,$product['seller_id']);

        //获取seo地址
        $seo_url                        = $this->model_catalog_handle->get_seo_url($product_id);

        //获取需要处理的选项产品
        $product_option_value_lists     = $this->model_catalog_handle->get_product_option_value_lists(['product_id'=>$product_id]);
        $code                           = [];
        if (!empty($product_option_value_lists)) {
            foreach ($product_option_value_lists as $key => $value) {
                $strcode               = md5($value['option_id'] . '-' . $value['option_value_id']);
                $code[$strcode]        = $strcode;
            }
        }

        sort($code);

        $variant_option_id_lists        = $this->model_catalog_handle->get_variant_option_id_lists($code);

        $variant_id                     = [];
        $variant_value                  = [];

        if (!empty($variant_option_id_lists)) {
            foreach ($variant_option_id_lists as $key => $value) {
                $varid                    = $value['variant_id'];
                $variant_id[$varid][]     = $value['variant_value_id'];
                foreach ($value as $kk => $vv) {
                    if ($kk == 'variant_value_id') {
                        $variant_value[$vv]  = $varid;
                    }
                }
            }
        }

        $combination          = $this->variant_combination($variant_id);

        if (isset($combination[0]) && $combination[0] > 0) {
            $this->model_catalog_handle->add_product($combination[0],$product_id,$product,$product_desc,$category,$seo_url);
        }

        if (isset($combination[1]) && !empty($combination[1]))
        {
            $products   = $this->model_catalog_handle->get_products($product_id);

            //设置店铺
            $this->model_catalog_handle->add_product_to_store($products);

            $variant    = [];

            foreach ($products as $key => $value) {
                $variant_value_id       = isset($combination[1][$key]) ? $combination[1][$key] : [];

                if (!empty($variant_value_id)) {
                    foreach ($variant_value_id as $kkk => $vvv) {
                        if (isset($variant_value[$vvv]) && $variant_value[$vvv] > 0) {
                            $variant[]        = ['product_id'=>$value['product_id'],'variant_id'=>$variant_value[$vvv],'variant_value_id'=>$vvv];
                        }
                    }
                }
            }

            $this->model_catalog_handle->add_product_variant($variant);
        }
    }

    private function echo_log($data = [],$is_save = false)
    {

        /*if (isset($data['ok']) && $data['ok'] == 1) {
            $this->model_catalog_handle->clear_table();
        }*/

        echo "      处理内容：" . (isset($data['handle_name']) ? $data['handle_name'] : '') . '<br>';
        echo "      处理总数：" . (isset($data['handle_num1']) ? $data['handle_num1'] : 0) . '<br>';
        echo "      已处理输：" . (isset($data['handle_num2']) ? $data['handle_num2'] : 0) . '<br>';
        echo "      未处理数：" . (isset($data['handle_num3']) ? $data['handle_num3'] : 0) . '<br>';
        echo "      当前处理：" . (isset($data['handle_info']) ? $data['handle_info'] : '') . '<br>';
        echo "      处理耗时：" . (isset($data['handle_time']) ? $data['handle_time'] : '');
    }

    private function jumpurl($url)
    {
        echo '<script type="text/javascript">window.location.href="' . str_replace('&amp;', '&', $url) . '"</script>';exit();
    }

    private function handle_variant($variant_id,$option_id,$is_add_variant = false)
    {
        //通过option_id和variant_id 获取选项值并且合并整理
        $option_value_description_list      = $this->model_catalog_handle->get_option_value_description_list(['option_id'=>$option_id]);
        $variant_value_description_list     = $this->model_catalog_handle->get_variant_value_description_list(['variant_id'=>$variant_id]);
        $option_value                       = [];
        $variant_value                      = [];
        $variant_option_id                  = [];
        $add_variant_value                  = [];

        if (!empty($option_value_description_list)) {
            foreach ($option_value_description_list as $key => $value) {
                $option_value[$value['name']]   = $value;
            }
        }

        if (!empty($variant_value_description_list)) {
            foreach ($variant_value_description_list as $key => $value) {
                $variant_value[$value['name']]   = $value;
            }
        }

        if (!empty($option_value)) {
            foreach ($option_value as $key => $value) {
                if (isset($variant_value[$key])) {
                    $variant_option_id[]    = [
                        'variant_id'        =>$variant_value[$key]['variant_id'],
                        'variant_value_id'  =>$variant_value[$key]['variant_value_id'],
                        'option_id'         =>$value['option_id'],
                        'option_value_id'   =>$value['option_value_id'],
                    ];
                }else{

                    //需要新增属性值
                    $add_variant_value[]    = [
                        'name'              =>$value['name'],
                    ];
                }
            }

            if (!empty($add_variant_value) && $is_add_variant) {
                $this->model_catalog_handle->add_variant_value_description(['variant_id'=>$variant_id,'variant_value'=>$add_variant_value]);
                $this->handle_variant($variant_id,$option_id,false);
            }

            if (!empty($variant_option_id) && !$is_add_variant ) {
                $this->model_catalog_handle->add_variant_option_id($variant_option_id);
            }
        }
    }

    //执行频率
    private function executionFrequency($time = 0 , $key = '')
    {
        if (empty($key) || $time <= 0) return true;

        $t                = time();
        $cache_time       = $this->cache->get($key);
        $cache_time       = !empty($cache_time) ? $cache_time : $t;

        if ($cache_time && $cache_time < $t) {

            $this->cache->set($key,$t + $time);
            return true;
        }

        $this->cache->set($key,$cache_time);

        return false;
    }

    private function recursion_foreach($data,$nums,$res = [])
    {
        $count           = count($data);
        $C               = [];
        foreach($data[$nums] as $v){
            $C[]    = array_merge($res,[$v]);
        }

        if ($count == ($nums + 1)) {
            return array_merge($res,$C);
        }else{
            return $this->recursion_foreach($data,($nums++),array_merge($res,$C));
        }
    }

    public function variant_combination($nums)
    {   
        $star           = count($nums);
        if ($star <= 1)  return [0,[]];

        sort($nums);

        $CN             = 1;

        foreach ($nums as $key => $value) {
            $CN         *= count($value);
        }

        $C              = [];

        if($CN > 0){
            switch ($star) {
                case 2:
                    foreach($nums[0] as $v0){//十位
                        foreach($nums[1] as $v1) $C[] = [$v0,$v1];//个位
                    }
                    break;
                case 3:
                    foreach($nums[0] as $v0){//百位
                        foreach($nums[1] as $v1){//十位
                            foreach($nums[2] as $v2) $C[] = [$v0,$v1,$v2];//个位
                        }
                    }
                    break;
                case 4:
                    foreach($nums[0] as $v0){//千位
                        foreach($nums[1] as $v1){//百位
                            foreach($nums[2] as $v2){//十位
                                foreach($nums[3] as $v3) $C[] = [$v0,$v1,$v2,$v3];//个位
                            }
                        }
                    }
                    break;
                case 5:
                    foreach($nums[0] as $v0){//万位
                        foreach($nums[1] as $v1){//千位
                            foreach($nums[2] as $v2){//百位
                                foreach($nums[3] as $v3){//十位
                                    foreach($nums[4] as $v4) $C[] = [$v0,$v1,$v2,$v3,$v4];//个位
                                }
                            }
                        }
                    }
                    break;
                default:return [0,[]];break;
            }
            
        }
        
        return [$CN,$C];
    }
}
