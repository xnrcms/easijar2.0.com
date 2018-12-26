<?php
class ControllerCrontabHandle extends Controller {

	//定时任务
	public function index() 
	{
        $this->load->model('catalog/handle');
        
        $req_data           = array_merge($this->request->get,$this->request->post);
        $step               = (isset($req_data['step']) && (int)$req_data['step'] > 0) ? (int)$req_data['step'] : 0;
        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 1;
        
        $filter_data        = [
            'start'    => ($page - 1) * $limit,
            'limit'    => $limit
        ];

        if ($step == 0) {
            $this->session->data['handle_data']     = [];

            //统计需要处理的商品数量
            $ptotals   = $this->model_catalog_handle->get_product_option_value_total();
            $ototals   = $this->model_catalog_handle->get_options_description_total();

            $this->session->data['handle_data']['ptotals']   = $ptotals;
            $this->session->data['handle_data']['ototals']   = $ototals;
            $this->session->data['handle_data']['data']      = ['ok'=>0];
            
            $this->echo_log();

            $this->jumpurl($this->url->link('crontab/handle','step=1'));
        }else if ($step == 2) {//处理商品
            
            //需要处理的商品列表
            $product_info                   = $this->model_catalog_handle->get_product_option_value_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['ptotals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['ptotals'] - $remainder;
            $is_save                        = $remainder > 0 ? true : false;
            $ok                             = $remainder > 0 ? 0 : 1;

            $log      = ['premainder1'=>$remainder1,'premainder'=>$remainder,'product_info'=>$product_info['product_id'] . '(' . $product_info['total'] . ')','ok'=>$ok];
            $this->echo_log(array_merge($log,$this->session->data['handle_data']['data']),$is_save);

            if ($remainder > 0) {

                //首先获取主产品信息
                $pinfo1                      = $this->model_catalog_handle->get_product($product_info['product_id']);
                $pinfo2                      = $this->model_catalog_handle->get_product_description($product_info['product_id']);
                $category                    = $this->model_catalog_handle->get_product_to_category($product_info['product_id']);
                $save_data1                  = $pinfo1;//商品数据
                $save_data2                  = $pinfo2;//商品详情数据
                $save_data3                  = [];//分类数据

                unset($save_data1['product_id']);
                unset($save_data1['sku']);
                unset($save_data2['language_id']);
                unset($save_data2['product_id']);

                if (!empty($category)) {
                    foreach ($category as $key => $value) {
                        $save_data3[$value['category_id']]   = $value['category_id'];
                    }

                    sort($save_data3);
                }

                $this->handle_product_info($product_info['product_id'],$save_data1,$save_data2,$save_data3);
                $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . ($page + 1)));
            }
        }else if ($step == 1) {//处理属性
            $option_info                    = $this->model_catalog_handle->get_options_description_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['ototals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['ototals'] - $remainder;
            $is_save                        = $remainder > 0 ? true : false;
            $option_id                      = $option_info['option_id'];

            $log    = ['oremainder1'=>$remainder1,'oremainder'=>$remainder,'option_info'=>$option_info['name'] . '(' . $option_info['option_id'] . ')'];
            $this->echo_log(array_merge($log,$this->session->data['handle_data']['data']),$is_save);

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

                $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . ($page + 1)));
            }else{
                $this->jumpurl($this->url->link('crontab/handle','step=' . ($step+1) . '&page=1'));
            }
        }else if ($step == 3) {//处理翻译
            # code...
        }

        echo "处理完成";exit();
    }

    private function handle_product_info($product_id,$product,$product_desc,$category)
    {
        if ( (int)$product_id <= 0 || empty($product) || empty($product_desc)) return;

        //修改主产品
        $this->model_catalog_handle->update_product($product_id);

        //获取需要处理的选项产品
        $product_option_value_lists     = $this->model_catalog_handle->get_product_option_value_lists(['product_id'=>$product_id]);
 
        //$product_count                  = count($product_option_value_lists);
        //if ($product_count <= 1) return;
        //$this->model_catalog_handle->add_product($product_count,$product_id,$product,$product_desc,$category);
        //$products   = $this->model_catalog_handle->get_products(1927);


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
            $this->model_catalog_handle->add_product($combination[0],$product_id,$product,$product_desc,$category);
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

        if (isset($data['ok']) && $data['ok'] == 1) {
            $this->model_catalog_handle->clear_table();
        }

        if (!$is_save) {
            $this->session->data['handle_data']['data']     = $data; return;
        }

        echo "        处理进度：" . (isset($data['ok']) && $data['ok'] == 1 ? '已完成' : '处理中') . '<br>';
        echo "      需整理产品：" . $this->session->data['handle_data']['ptotals'] . '<br>';
        echo "      已处理产品：" . (isset($data['premainder1']) ? $data['premainder1'] : 0) . '<br>';
        echo "      未处理产品：" . (isset($data['premainder']) ? (int)$data['premainder'] : $this->session->data['handle_data']['ptotals']) . '<br>';
        echo "    当前处理产品：" . (isset($data['product_info']) ? $data['product_info'] : 0) . '<br>';
        echo "      需整理属性：" . $this->session->data['handle_data']['ototals'] . '<br>';
        echo "      已处理属性：" . (isset($data['oremainder1']) ? (int)$data['oremainder1'] : 0) . '<br>';
        echo "      未处理属性：" . (isset($data['oremainder']) ? $data['oremainder'] : $this->session->data['handle_data']['ototals']) . '<br>';
        echo "    当前处理属性：" . (isset($data['option_info']) ? $data['option_info'] : '') . '<br>';
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
