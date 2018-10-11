<?php
class ControllerExtensionModuleHubtalk extends Controller {
    public function index() {

        $langID = $this->config->get('config_language_id'); 
        $store = $this->config->get('config_store_id');

        $data['module_hubtalk_code']=html_entity_decode($this->config->get('module_hubtalk_code_'.$store.'_'.$langID), ENT_QUOTES, 'UTF-8');
        $data['module_hubtalk_opencart_v'] = VERSION;
		return $this->load->view('extension/module/hubtalk', $data);

	}

    public function cart(){
        header('Content-Type: application/json');
        $cartObj=array();

        $currency = $this->config->get('config_currency');


        $this->load->model('tool/image');

        $cart = $this->cart->getProducts();
        for($i=0;$i<sizeof($cart);$i++ ){

        if ($cart[$i]["image"]) {
			$image = $this->model_tool_image->resize($cart[$i]["image"], 60, 60);
		} else {
			$image = '';
		}

            $cartObj[$i] = array(
                "name" => $cart[$i]["name"],
                "product_code" => $cart[$i]["model"],
                "image" => $image,
                "quantity" => $cart[$i]["quantity"],
                "price" => $currency.' '.$cart[$i]["price"],
                "total" => $currency.' '.$cart[$i]["total"],
                "option" => ""
            );
            if(sizeof($cart[$i]["option"])>0){
                for($j=0;$j<sizeof($cart[$i]["option"]);$j++ ){
                    $cartObj[$i]["option"].=$cart[$i]["option"][$j]['name'].": ".$cart[$i]["option"][$j]['value']."; ";
                }
            }else
                $cartObj[$i]["option"] = "-";

        }


        echo(json_encode($cartObj));
        exit();
    }

    public function account(){

        header('Content-Type: application/json');

        $this->load->model('account/order');

        $accountObj = array(
            "name" => trim($this->customer->getFirstname()." ".$this->customer->getLastname()),
            "email" => $this->customer->getEmail(),
            "phone" => $this->customer->getTelephone(),
            "total_orders" => intval($this->model_account_order->getTotalOrders())
        );

        $ordersObj = array();

        $orders = $this->model_account_order->getOrders();
        for($i=0;$i<sizeof($orders);$i++){
            $ordersObj[$i] = array(
                "id" => $orders[$i]['order_id'],
                "status" => $orders[$i]['status'],
                "total" => $orders[$i]['total'],
                "currency_code" => $orders[$i]['currency_code']
            );
            $products = $this->model_account_order->getOrderProducts($orders[$i]['order_id']);
            
            $ordersObj[$i]["products"] = $this->model_account_order->getOrderProducts($orders[$i]['order_id']);

        }

        $accountObj["orders"] = $ordersObj;

        echo(json_encode($accountObj));

        exit();
    }

    public function product(){
        header('Content-Type: application/json');

        $productObj = array();

        if (isset($this->request->post['id'])){

            $currency = $this->config->get('config_currency');

            $productID = $this->request->post['id'];
            $this->load->model('catalog/product');
            $this->load->model('tool/image');
            $product = $this->model_catalog_product->getProduct($productID);

            if($product){

                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], 60, 60);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', 60, 60);
                }

                $productObj = array(
                    "name" => $product['name'],
                    "model" => $product['model'],
                    "price" => $currency.$product['price'],
                    "image" => $image,
                    "link" =>  $this->url->link('product/product', 'product_id=' . $productID, 'canonical')
                );
            }

        }
        echo(json_encode($productObj));

        exit();
    }



    public function search(){

        header('Content-Type: application/json');

        $searchObj=array();

        if (isset($this->request->post['q'])){

            $this->load->model('catalog/product');
            $this->load->model('tool/image');

            $filter_data = array(
                'filter_name'         => $this->request->post['q'],
                'start'               => 0,
                'limit'               => 10
            );
            $results = $this->model_catalog_product->getProducts($filter_data);


            $currency = $this->config->get('config_currency');

            foreach($results as $productID => $product){

                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], 60, 60);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', 60, 60);
                }

                $searchObj[] = array(
                    "name"  => $product['name'],
                    "model" => $product['model'],
                    "price" => $currency.$product['price'],
                    "quantity" =>$product['quantity'],
                    "image" => $image,
                    "link"  =>  $this->url->link('product/product', 'product_id=' . $product['product_id'], 'canonical')
                );
            }

        }
        echo(json_encode($searchObj));

        exit();
    }
}
