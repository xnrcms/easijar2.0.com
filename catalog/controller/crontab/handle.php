<?php
class ControllerCrontabHandle extends Controller {

	//定时任务
	public function index() 
	{
        set_time_limit(0);
        $this->load->model('catalog/handle');
        
        $req_data           = array_merge($this->request->get,$this->request->post);
        $step               = (isset($req_data['step']) && (int)$req_data['step'] > 0) ? (int)$req_data['step'] : 0;
        $page               = (isset($req_data['page']) && (int)$req_data['page'] >=1) ? (int)$req_data['page'] : 1;
        $limit              = (isset($req_data['limit']) && (int)$req_data['limit'] > 0) ? (int)$req_data['limit'] : 1;
        $ini_time           = time();

        $filter_data        = [
            'start'         => ($page - 1) * $limit,
            'limit'         => $limit,
            'product_id'    =>58216,
            'language_id'   => 1
        ];

        if ($step == 0) {
            $this->session->data['handle_data']     = [];
            
            //统计需要处理的数据
            $ptotals   = $this->model_catalog_handle->get_product_option_value_total($filter_data);
            $ototals   = $this->model_catalog_handle->get_options_description_total($filter_data);
            $vtotals   = $this->model_catalog_handle->get_variant_description_total($filter_data);
            $pdtotals  = $this->model_catalog_handle->get_product_description_totals($filter_data);

            $this->session->data['handle_data']['ptotals']   = $ptotals;
            $this->session->data['handle_data']['ototals']   = $ototals;
            $this->session->data['handle_data']['vtotals']   = $vtotals;
            $this->session->data['handle_data']['pdtotals']  = $pdtotals;
            $this->session->data['handle_data']['data']      = ['ok'=>0];

            $this->echo_log([
                'handle_name'=>'处理数据准备中'
            ]);
            //print_r($filter_data);exit();
            $this->jumpurl($this->url->link('crontab/handle','step=16'));
        }else if ($step == 16) {//综合处理
            $totals        = $this->model_catalog_handle->getProductsTotals($filter_data);
            $plist         = $this->model_catalog_handle->getProducts($filter_data);
            
            if (!empty($plist))
            {
                foreach ($plist as $product) {
                    //单个商品逐个处理
                    //第一步 整理商品名称并且做翻译
                    $this->handle_product_info_for_name($product);
                    
                    //第二步 整理商品列表,生成子商品
                    $this->handle_product_children($product);
                }

                $page ++;
            }else{
                $page   = 0;
                $step   = 100;
            }

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }
        else if ($step == 2) {//处理商品
            echo "ok";exit();
            //需要处理的商品列表
            $product_info                   = $this->model_catalog_handle->get_product_option_value_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['ptotals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['ptotals'] - $remainder;
            $is_save                        = $remainder > 0 ? true : false;
            $ok                             = $remainder > 0 ? 0 : 1;

            $shop   = [108=>1,110=>2,130=>3,112=>4,113=>5,114=>6,153=>7,184=>8];

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
                
                $this->handle_product_info($product_info['product_id'],$save_data1,$save_data2,$save_data3);

                $page ++;
            }else{
                $page = 0;
                $step ++;
            }

            $this->echo_log([
                'handle_name'=>'商品子商品添加',
                'handle_num1'=>$this->session->data['handle_data']['ptotals'],
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'主商品ID：'.$product_info['product_id'] . '(子产品数：' . $product_info['total'] . ')',
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);
            //exit();

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }else if ($step == 1) {//处理属性

            $option_info                    = $this->model_catalog_handle->get_options_description_list($filter_data);
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
        }else if ($step == 3) {//处理属性翻译
            echo "ok";exit();
            $language_id                    = 1;
            $filter_data['language_id']     = $language_id;
            $variant_info                   = $this->model_catalog_handle->get_variant_description_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['vtotals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['vtotals'] - $remainder;
            $variant_id                     = $variant_info['variant_id'];

            if ($remainder > 0) {

                if (!empty($variant_info)) {
                   $name = $this->handle_translate($variant_info['name']);
                   if (!empty($name)) {
                        $this->model_catalog_handle->update_variant_description_name($variant_id,$language_id,$name);
                   }
                }

                $page ++;
            }else{
                $page = 0;
                $step ++;
            }

            $this->echo_log([
                'handle_name'=>'商品属性中文翻译',
                'handle_num1'=>$this->session->data['handle_data']['vtotals'],
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'属性名称：'.$variant_info['name'] . '(属性ID：' . $variant_info['variant_id'] . ')',
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }else if ($step == 4) {
            echo "ok";exit();
            $this->model_catalog_handle->clear_table();

            echo "处理完成";exit();

            //$this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }else if ($step == 5) {
            echo "ok";exit();
            //处理文章详情里面的URL链接
            $info                           = $this->model_catalog_handle->get_product_description_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['pdtotals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['pdtotals'] - $remainder;
            $is_save                        = $remainder > 0 ? true : false;
            $option_id                      = $info['product_id'];

            if ($remainder > 0) {

                if (!empty($info)) {
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

                $page ++;
            }else{
                $page = 0;
                $step ++;
            }

            $this->echo_log([
                'handle_name'=>'商品详情信息过滤',
                'handle_num1'=>$this->session->data['handle_data']['pdtotals'],
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'详情名称：'.$info['name'] . '(详情ID：' . $info['product_id'] . ')',
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }else if ($step == 6) {

            //刪除下架数据
            $plist      = $this->model_catalog_handle->get_product_status0();
            $plist1     = $this->model_catalog_handle->get_product_status1();
            $ids        = [];

            foreach ($plist1 as $key1 => $value1) {
                $ids[] = (int)$value1['product_id'];
            }

            foreach ($plist as $key => $value) {
                $ids[] = (int)$value['product_id'];
            }

            $ids = array_flip(array_flip($ids));

            $this->model_catalog_handle->del_product_status0($ids);
            echo 'del ok';exit();
        }
        else if ($step == 7) {//处理分类以及店铺绑定
            echo "ok";exit();
            //分类在处理
            $info                           = $this->model_catalog_handle->get_product_description_list($filter_data);
            $remainder                      = intval($this->session->data['handle_data']['pdtotals'] - $limit * $page);
            $remainder1                     = $this->session->data['handle_data']['pdtotals'] - $remainder;

            if ($remainder > 0) {
                $categorys                      = $this->model_catalog_handle->get_product_to_category($info['product_id']);
                if (!empty($categorys)) {
                    $cat_ids                     = [];
                    foreach ($categorys as $key => $value) {
                        $category_levels        = $this->model_catalog_handle->get_category_path_level($value['category_id'],1);
                        if (!empty($category_levels)) {
                            $cat_ids[]   = (int)$category_levels['category_id'];
                            $cat_ids[]   = (int)$category_levels['path_id'];
                        }else{
                            $cat_ids[]   = (int)$value['category_id'];
                        }
                    }

                    $cat_ids[]           = 0;

                    $cats                = $this->model_catalog_handle->get_category_path($cat_ids);

                    $shop                = [108=>1,110=>2,130=>3,112=>4,113=>5,114=>6,153=>7,184=>8];
                    $seller_id           = 0;
                    $product_cats        = [];
                    foreach ($cats as $key => $value) {
                        $product_cats[$value['path_id']]  = $value['path_id'];
                        if (isset($shop[$value['path_id']])) {
                            $seller_id   = $shop[$value['path_id']];
                        }
                    }

                    //添加商品到分类
                    $this->model_catalog_handle->add_product_to_category2($info['product_id'],$product_cats);
                    $this->model_catalog_handle->add_ms_product_seller($info['product_id'],$seller_id);
                }else{
                    wr([$info['product_id']]);
                }

                $page ++;
            }else{
                $page = 0;
                $step = 6;
            }

            $this->echo_log([
                'handle_name'=>'商品分类和店铺归类',
                'handle_num1'=>$this->session->data['handle_data']['pdtotals'],
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'商品ID：'.$info['product_id'],
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }else if( $step == 8){//修正语言
echo "ok";exit();
            $limit              = 1000;
            $filter_data        = [
                'start'    => ($page - 1) * $limit,
                'limit'    => $limit
            ];
            
            $total                          = $this->model_catalog_handle->get_product_count();
            $list                           = $this->model_catalog_handle->get_product_list($filter_data);
            $remainder                      = intval($total - $limit * $page);
            $remainder1                     = $total - $remainder;
            $product_ids                    = [];
            $error                          = [];
            $no_vars                        = [];

            if (!empty($list)) {
                foreach ($list as $key => $value) {
                    $product_id         = $value['product_id'];
                    $pdinfo1            = $this->model_catalog_handle->get_product_description_for_product_id($product_id,1);
                    $pdinfo2            = $this->model_catalog_handle->get_product_description_for_product_id($product_id,2);

                    $product_ids[]      = $product_id;

                    if (empty($pdinfo1)) {
                        $error['pdinfo1'][] = $product_id;
                    }

                    if (empty($pdinfo2)) {
                        $error['pdinfo2'][] = $product_id;
                    }

                    if (!empty($pdinfo1) && !empty($pdinfo2)) {
                        $total2  = $this->model_catalog_handle->get_product_count($product_id);
                        $list2   = $this->model_catalog_handle->get_product_list(['start'=>0,'limit'=>1000],$product_id);
                        if (!empty($list2)) {
                            $pids = [];
                            foreach ($list2 as $key => $value) {
                                $pids[] = $value['product_id'];
                            }

                            $pids[] = 0;

                            $updata                         = [];
                            $updata['product_ids']          = $pids;
                            $updata['language_id']          = 2;
                            $updata['name']                 = $pdinfo2['name'];
                            $updata['tag']                  = $pdinfo2['name'];
                            $updata['meta_title']           = $pdinfo2['name'];
                            $updata['meta_description']     = $pdinfo2['name'];
                            $updata['meta_keyword']         = $pdinfo2['name'];

                            //$this->model_catalog_handle->update_product_descriptions($updata);

                        }else{
                            $no_vars[] = $product_id;
                        }
                    }
                }

                if (!empty($no_vars))
                {
                    $this->model_catalog_handle->del_product_variant($no_vars);
                
                    $variants = [];
                    foreach ($no_vars as $key => $value) {
                        $variants[]     = ['product_id'=>$value,'variant_id'=>10,'variant_value_id'=>11078];
                    }

                    $this->model_catalog_handle->add_product_variant($variants);
                }

                $page ++;
            }else{
                $page = 0;
                $step = 1000;
            }

            if (!empty($error)) {
                wr($error);
            }

            $remainder                      = intval($total - $limit * $page);

            $this->echo_log([
                'handle_name'=>'商品修正语言',
                'handle_num1'=>$total,
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'页数：'.$page,
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));

        }else if($step == 1000){
            echo "ok";exit();
        }else if ($step == 9) {//修正英文名称含有中文问题
            $path = '111.xlsx';echo "ok";exit();
            include 'Classes/PHPExcel.php';            
            include 'Classes/PHPExcel/IOFactory.php';

            $type       = 'Excel2007';//设置为Excel5代表支持2003或以下版本， Excel2007代表2007版
            $xlsReader  = \PHPExcel_IOFactory::createReader($type);  
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets     = $xlsReader->load($path);
            //开始读取上传到服务器中的Excel文件，返回一个 二维数组
            $dataArray = $Sheets->getSheet(0)->toArray();

            unset($dataArray[0]);

            /*print_r($dataArray);exit();
            $dataArray      = [];
            $dataArray[0] = [15635,'',"Xia Xin's short-sleeved cotton T-shirt, stool and chair printed decals"];
            $dataArray[1] = [19185,'',"Pearl Chiffon wrinkled Muslim women's headscarves wholesale 2018 Hui clothing wrinkled hot selling headscarves"];*/

            foreach ($dataArray as $key => $value) {
                $pinfo  = $this->model_catalog_handle->get_product($value[0]);
                if (!empty($pinfo)) {
                    $product_ids        = [];
                    if ($pinfo['parent_id'] == 0) {
                        $product_id     = $value[0];
                    }else{
                        $product_id     = $pinfo['parent_id'];
                    }

                    $product_ids[]  = $product_id;

                    $list   = $this->model_catalog_handle->get_product_list(['start'=>0,'limit'=>1000],$product_id);

                    foreach ($list as $k => $v) {
                        $product_ids[]  = $v['product_id'];
                    }

                    $updata                         = [];
                    $updata['product_ids']          = $product_ids;
                    $updata['language_id']          = 1;
                    $updata['name']                 = $value[2];
                    $updata['tag']                  = $updata['name'];
                    $updata['meta_title']           = $updata['name'];
                    $updata['meta_description']     = $updata['name'];
                    $updata['meta_keyword']         = $updata['name'];

                    $this->model_catalog_handle->update_product_descriptions($updata);
                }else{
                    wr($value);
                }
            }
            echo "ok";exit();
        }else if ($step == 10) {echo "ok";exit();
            $list = $this->model_catalog_handle->get_product_description2();

            foreach ($list as $key => $value) {

                $product_ids        = [];
                if ($value['parent_id'] == 0) {
                    $product_id     = $value['product_id'];
                }else{
                    $product_id     = $value['parent_id'];
                }

                $product_ids[]  = $product_id;

                $list2   = $this->model_catalog_handle->get_product_list(['start'=>0,'limit'=>1000],$product_id);

                foreach ($list2 as $k => $v) {
                    $product_ids[]  = $v['product_id'];
                }

                $updata                         = [];
                $updata['product_ids']          = $product_ids;
                $updata['language_id']          = $value['language_id'];
                /*$updata['name']                 = $value['name'];*/
                $name = $value['language_id'] == 1 ? str_replace([' s925 ',' S925 ',' 925 ','S925','s925','925'], [' ',' ',' ',' ',' ',' '], $value['name']):str_replace([' s925 ',' S925 ',' 925 ','S925','s925','925'], ['','','','','',''], $value['name']);;
                $updata['name']                 = $name;
                $updata['tag']                  = $updata['name'];
                $updata['meta_title']           = $updata['name'];
                $updata['meta_description']     = $updata['name'];
                $updata['meta_keyword']         = $updata['name'];
                
                $this->model_catalog_handle->update_product_descriptions($updata);
                /*if ($key == 51) {
                    print_r($updata);exit();
                }*/
                //$list2   = $this->model_catalog_handle->get_product_list(['start'=>0,'limit'=>1000],$value['product_id']);
                //print_r($product_ids);exit();
            }
            echo "ok";exit();
        }else if ($step == 11) {
            $limit              = 12000;
            $filter_data        = [
                'start'    => ($page - 1) * $limit,
                'limit'    => $limit
            ];
            
            $total                          = $this->model_catalog_handle->get_variant_value_count();
            $list                           = $this->model_catalog_handle->get_variant_value_list($filter_data);

            if (!empty($list))
            {
                foreach ($list as $key => $value) {
                    $plist  = $this->model_catalog_handle->get_product_variant_list1($value['variant_id'],$value['variant_value_id']);

                    $pids   = [];
                    foreach ($plist as $k => $v) {
                        $pids[$v['product_id']] = $v['product_id'];
                    }

                    sort($pids);

                    $plist2     = $this->model_catalog_handle->get_product_status3($pids);

                    $dels_pid   = array_flip($pids);

                    foreach ($plist2 as $kk => $vv) {
                        if (isset($dels_pid[(int)$vv['product_id']])) {
                            unset($dels_pid[(int)$vv['product_id']]);
                        }
                    }

                    $dels_pid   = array_flip($dels_pid);
                    wr(['dels_pid'=>$dels_pid]);

                    $this->model_catalog_handle->del_product_variant($dels_pid);

                    $plist2  = $this->model_catalog_handle->get_product_variant_list1($value['variant_id'],$value['variant_value_id']);

                    if (empty($plist2)) {
                        wr(['del_variant_value'=>[$value['variant_id'],$value['variant_value_id']]]);
                        $this->model_catalog_handle->del_variant_value($value['variant_id'],$value['variant_value_id']);
                    }
                }

                $page ++;
            }
            else{
                //$page = 0;
                $step = 1000;
            }
                        
            $remainder                      = intval($total - $limit * $page);
            $remainder1                     = $total - $remainder;

            $this->echo_log([
                'handle_name'=>'商品属性剔除',
                'handle_num1'=>$total,
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'页数：'.$page,
                'handle_time'=>(time()-$ini_time) . '秒',
            ]);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&page=' . $page));
        }else if ($step == 12) {
            echo "ok=";exit();
            //$count      = $this->model_catalog_handle->get_product_count(0);
            $list       = $this->model_catalog_handle->get_product_list(['start'=>0,'limit'=>10000],0);
            $add_count  = 0;

            foreach ($list as $key => $value) {

                //通过主产品ID获取相册
                $product_id         = $value['product_id'];
                $images             = $this->model_catalog_handle->get_product_images($product_id);

                if (empty($images)) {
                    wr(['empty($images)'=>$product_id]);
                    continue;
                }

                $list2              = $this->model_catalog_handle->get_product_list(['start'=>0,'limit'=>1000],$product_id);

                $add_images         = [];
                foreach ($list2 as $k => $v) {
                    foreach ($images as $key => $value) {
                        $add_images[]  = ['product_id'=>$v['product_id'],'image'=>$value['image']];
                    }
                }

                if (!empty($add_images)) {

                    $add_count ++;

                    $this->model_catalog_handle->add_product_image($add_images);
                }else{
                    wr(['empty($add_images)'=>$product_id]);
                    continue;
                }
            }

            echo "ok=".$add_count;exit();
        }else if ($step == 13) {
            $path = 'attr.xlsx';
            include 'Classes/PHPExcel.php';            
            include 'Classes/PHPExcel/IOFactory.php';

            $type       = 'Excel2007';//设置为Excel5代表支持2003或以下版本， Excel2007代表2007版
            $xlsReader  = \PHPExcel_IOFactory::createReader($type);  
            $xlsReader->setReadDataOnly(true);
            $xlsReader->setLoadSheetsOnly(true);
            $Sheets     = $xlsReader->load($path);
            //开始读取上传到服务器中的Excel文件，返回一个 二维数组
            $dataArray = $Sheets->getSheet(2)->toArray();

            unset($dataArray[0]);

            foreach ($dataArray as $key => $value) {
                $variant_value      = (isset($value[1]) && !empty($value[1])) ? trim($value[1]) : '';
                $variant_zh         = (isset($value[2]) && !empty($value[2])) ? trim($value[2]) : '';
                $variant_en         = (isset($value[3]) && !empty($value[3])) ? trim($value[3]) : '';
                $variant_mark       = (isset($value[4]) && !empty($value[4])) ? trim($value[4]) : '';

                if (empty($variant_value) && empty($variant_zh) && empty($variant_en) && empty($variant_mark)) {
                    continue;
                }

                if (empty($variant_value)) {
                    wr($value,'attr1.txt');
                    continue;
                }

                //根据属性值获取数据
                $variant_value_description    = $this->model_catalog_handle->get_variant_value_description($variant_value);
                if (!$variant_value_description) {
                    wr($value,'attr2.txt');
                    continue;
                }

                //根据备注相应处理
                if ($variant_mark == '删除' || $variant_mark == '删掉' || strpos( '@'.$variant_mark,'下架EJ') !== false) {
                    wr($value,'attr3.txt');
                    foreach ($variant_value_description as $vvk => $vvv)
                    {
                        $variant_id          = $vvv['variant_id'];
                        $variant_value_id    = $vvv['variant_value_id'];
                        //删掉属性相关数据
                        $this->model_catalog_handle->del_variant_data($variant_id,$variant_value_id);
                    }

                    continue;
                }

                if (empty($variant_zh) || empty($variant_en)) {
                    wr($value,'attr4.txt');
                    continue;
                }

                foreach ($variant_value_description as $vvk => $vvv)
                {
                    $variant_id          = $vvv['variant_id'];
                    $variant_value_id    = $vvv['variant_value_id'];

                    //屬性重置
                    $this->model_catalog_handle->set_variant_value_description($variant_value,$variant_id,$variant_value_id,1);
                    $this->model_catalog_handle->set_variant_value_description($variant_value,$variant_id,$variant_value_id,2);

                    //属性值跟修改后的属性值如果一样不需要整理对应的产品
                    if ( $variant_value === $variant_zh ) {
                        $this->model_catalog_handle->set_variant_value_description($variant_en,$variant_id,$variant_value_id,1);
                        $this->model_catalog_handle->set_variant_value_description($variant_zh,$variant_id,$variant_value_id,2);
                    }else{
                        //转移属性
                        $variant_value_description2    = $this->model_catalog_handle->get_variant_value_description2($variant_zh);
                        if (empty($variant_value_description2)) {
                            $this->model_catalog_handle->set_variant_value_description($variant_en,$variant_id,$variant_value_id,1);
                            $this->model_catalog_handle->set_variant_value_description($variant_zh,$variant_id,$variant_value_id,2);
                        }else{

                            $this->model_catalog_handle->move_variant_product($vvv,$variant_value_description2);
                        }
                    }
                }
            }

            echo "ok";exit();
        }else if($step == 14){
            echo "ok";exit();
            $st_time    = date('2018-12-01 00:00:00');
            $en_time    = date('2019-01-06 00:00:00');
            $day        = isset($req_data['day']) ? (int)$req_data['day'] : 0;
            $nums       = isset($req_data['nums']) ? (int)$req_data['nums'] : 0;
            $now_time   = strtotime($st_time) + 86400 * $day;

            if ($now_time >= strtotime($en_time)) {
                echo "ok=".$nums;exit();
            }

            $ymd        = date('Y/m/d',$now_time);
            $dirs1      = DIR_IMAGE . 'catalog/shopfw/'.$ymd.'/main/*';
            $files1     = glob($dirs1);

            $dirs2      = DIR_IMAGE . 'catalog/shopfw/'.$ymd.'/description/*';
            $files2     = glob($dirs2);

            $image_path = [];

            if (!empty($files1)) {
                foreach ($files1 as $val1) {
                    $image_path[] = str_replace(DIR_IMAGE,'',$val1);
                }
            }

            if (!empty($files2)) {
                foreach ($files2 as $val2) {
                    $image_path[] = str_replace(DIR_IMAGE,'',$val2);
                }
            }

            $this->model_catalog_handle->add_image_path($image_path);

            $day ++;
            $nums   = $nums + count($image_path);

            $this->jumpurl($this->url->link('crontab/handle','step=' . $step . '&day=' . $day . '&nums='.$nums));
        }else if ($step == 15) {
            $limit              = 1000;
            $filter_data        = [
                'start'    => ($page - 1) * $limit,
                'limit'    => $limit
            ];
            
            $total                          = 105922;
            $list                           = $this->model_catalog_handle->get_product_list_for_image($filter_data);

            if (!empty($list))
            {
                $image_path         = [];
                $product_ids        = [];

                foreach ($list as $key => $value) {
                    $image_path[$value['image']]        = $value['image'];
                    $product_ids[$value['product_id']]  = $value['product_id'];

                    $desc   = htmlspecialchars_decode($value['description']);
                    preg_match_all('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i',$desc,$match);

                    if (isset($match[2]) && !empty($match[2])) {
                        foreach ($match[2] as $k => $v) {
                            if (strpos($v, 'image/catalog/shopfw') >= 1) {
                                $img                = str_replace("../image/", '', $v);
                                $image_path[$img]   = $img;
                            }
                        }
                    }
                }

                if (!empty($product_ids)) {
                    $images         = $this->model_catalog_handle->get_product_images_for_image($product_ids);
                    if (!empty($images)) {
                        foreach ($images as $kk => $vv) {
                            if(!empty($vv['image'])){
                                $image_path[$vv['image']]   = $vv['image'];
                            }
                        }
                    }
                }

                $this->model_catalog_handle->add_image_path2($image_path);

                $page ++;
            }
            else{
                $step = 1000;
            }

            $remainder                      = intval($total - $limit * $page);
            $remainder1                     = $total - $remainder;

            $this->echo_log([
                'handle_name'=>'商品属性剔除',
                'handle_num1'=>$total,
                'handle_num2'=>$remainder1,
                'handle_num3'=>$remainder,
                'handle_info'=>'页数：'.$page,
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


    private function filter_keywords($content = '')
    {
        if ($content) {
            $filter         = ['蓓尔','混批','批','一件代发','直供','抖音','大量批发','赛美图','招代理','ebay','亚马逊','速卖通','跨境','电商','批发','直销','欧美','欧洲站','【起念】','起念','一件','代发','wish','EBAY','外贸','2015','2016','2017','2018','热销款','热卖','厂家','包邮','直邮','网红','彩月','原宿','奈珠','青岛','思可图','红嘎嘎','ins','露曼尼','蓉时代','kiss the rain','kiss','the','rain','工厂','现货','供应','抗起球','宝娜斯','VENVEN','SUSANNY','Siti Selected','Siti','Selected','实拍','主推','特价','清仓','处理','享瘦版','专供','防伪标','JASMIN','地摊','主推','思可图','通勤','思可图','红嘎嘎','森帛','范思蓝恩','敦煌','洋气','娜笛','定制款','【高端质量】','【高端】','KJ','品牌','工厂价','出口','中东','BA','迪拜','批发','加工','定制','申立','susanny','Kiss','RGG E','W',' '];
            $filter_empty   = [];
            $filter_count   = count($filter);

            for ($i=0; $i < $filter_count; $i++) {
                $filter_empty[] = '';
            }

            $content    = str_replace($filter, $filter_empty, $content);
        }

        return trim($content);
    }

    public function clear_order()
    {
        set_time_limit(0);
        $this->load->model('catalog/handle');

        $customer_id    = 9;
        $oids           = [];

        $order_ids      = $this->model_catalog_handle->get_all_order_by_customer_id($customer_id);
        foreach ($order_ids as $key => $value) {
            $oids[$value['order_id']]   = $value['order_id'];
        }

        $oids[0] = 0;

        $this->model_catalog_handle->del_order_by_order_ids($oids);
        echo "ok";exit();
    }
}
