<?php
class ControllerApiSellerRegister extends Controller
{
    public function index()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('seller/register');

        $allowKey       = ['api_token','account','password','confirm','verification_code','source'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $this->load->model('account/customer');

        $validate       = $this->register_validate($req_data);

        if ( isset($validate['code']) && $validate['code'] == '205' ) {
            //$validate['data']['url']    = htmlspecialchars_decode($this->url->link('seller/login/index', 'code='.$req_data['api_token']));
            return $this->response->setOutput($this->returnData($validate));
        }

        if ( !(isset($validate['code']) && $validate['code'] == '200') ) {
            return $this->response->setOutput($this->returnData($validate));
        }

        $req_data['email']          = $req_data['account'];
        $req_data['telephone']      = '';
        $req_data['fullname']       = '';
        $req_data['custom_field']   = '';
        $req_data['newsletter']     = 0;
        $req_data['safe']           = 0;
        $req_data['status']         = 0;
        $req_data['country_id']     = 44;

        $customer_id = $this->model_account_customer->addCustomer($req_data);

        unset($this->session->data['smscode']);

        $this->load->model('multiseller/seller');
        $this->model_multiseller_seller->saveSeller($customer_id, $req_data);

        // Clear any previous login attempts for unregistered accounts.
        if (!$this->config->get('module_multiseller_seller_approval')) {
            // Clear any previous login attempts in not registered.
            $this->model_account_customer->deleteLoginAttempts($customer_id);
        }

        $this->session->data['customer_id']         = $customer_id;

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'register success']));
    }

    private function register_validate($req_data = [])
    {
        if ( !array_get($req_data, 'account') || ((utf8_strlen($req_data['account']) > 96) || !filter_var($req_data['account'], FILTER_VALIDATE_EMAIL))) {
            return ['msg'=>$this->language->get('error_email')];
        }

        if (array_get($req_data, 'account') && $this->model_account_customer->getTotalCustomersByEmail($req_data['account'])) {
            return ['code'=>'205','msg'=>$this->language->get('error_exists')];
        }

        //校验密码复杂程度
        $password               = html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8');
        if (empty($password) || utf8_strlen($password) < 6 || utf8_strlen($password) > 32 ) {
            return ['msg'=>$this->language->get('error_password')];
        }

        if (preg_match_all("/[`~!@#$%^&*()\-_=+{};:<,.>?\/]/",$password) < 1){
            return ['msg'=>$this->language->get('error_password2')];
        }

        if (preg_match_all("/^[a-zA-Z\d_~!@#$%^&*()\-_=+{};:<,.>?\/]{6,32}$/",$password) < 1){
            return ['msg'=>$this->language->get('error_password1')];
        }

        if ($req_data['confirm'] !== $req_data['password']) {
            return ['msg'=>$this->language->get('error_confirm')];
        }

        $keys                                       = md5('smscode-' . $req_data['account'] . '-1');
        if ((utf8_strlen(html_entity_decode($req_data['verification_code'], ENT_QUOTES, 'UTF-8')) != 6) || !isset($this->session->data['smscode'][$keys]) || $req_data['verification_code'] != $this->session->data['smscode'][$keys]['code'] ||  $this->session->data['smscode'][$keys]['expiry_time'] < time()){
            return ['msg'=>$this->language->get('error_smscode')];
        }

        return ['code'=>'200'];
    }

    //入驻资料完善
    public function perfect()
    {
        $this->response->addHeader('Content-Type: application/json');
        $this->load->language('seller/register');

        $allowKey       = ['api_token','true_name','telephone','verification_code','zone_id','city_id','county_id','address','store_name','experience','company_type','company','license','legal_person','idnum','images'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!isset($req_data['api_token']) || (int)(utf8_strlen(html_entity_decode($req_data['api_token'], ENT_QUOTES, 'UTF-8'))) !== 26) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:api_token error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $customer_id     = isset($this->session->data['customer_id']) ? (int)$this->session->data['customer_id'] : 0;
        if ($customer_id <= 0) {
            return $this->response->setOutput($this->returnData(['code'=>'206','msg'=>'fail:user info error']));
        }

        $this->load->model('account/customer');

        $validate       = $this->perfect_validate($req_data);

        if ( !(isset($validate['code']) && $validate['code'] == '200') ) {
            return $this->response->setOutput($this->returnData($validate));
        }

        $this->load->model('multiseller/seller');
        $this->model_multiseller_seller->saveSeller($customer_id, $req_data);

        /*//删除保存的临时图片
        if (!empty($req_data['images']))
        {
            $this->load->model('tool/image');

            $imgs        = explode(',', $req_data['images']);

            $this->model_tool_image->del_temp_image($imgs);
        }*/

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'perfect success']));
    }

    private function perfect_validate($req_data = [])
    {
        if ((utf8_strlen(trim($req_data['true_name'])) < 2) || (utf8_strlen(trim($req_data['true_name'])) > 10)) {
            return ['msg'=>$this->language->get('error_true_name')];
        }

        $telephone      = trim(array_get($req_data, 'telephone',''),'+');

        $telephones     = explode('-', $telephone);
        if (count($telephones) < 2 || !strlen($telephones[0]) || !strlen($telephones[1] || strlen($telephones[0]) > 4)) {
            return ['msg'=>$this->language->get('error_telephone')];
        }

        if ($this->model_account_customer->getTotalCustomersByTelephone($telephone)) {
            return ['msg'=>$this->language->get('error_exists_telephone')];
        }

        $keys                                       = md5('smscode-' . $req_data['telephone'] . '-1');
        if ((utf8_strlen(html_entity_decode($req_data['verification_code'], ENT_QUOTES, 'UTF-8')) != 6) || !isset($this->session->data['smscode'][$keys]) || $req_data['verification_code'] != $this->session->data['smscode'][$keys]['code'] ||  $this->session->data['smscode'][$keys]['expiry_time'] < time()){
            return ['msg'=>$this->language->get('error_smscode')];
        }

        if ((int)$req_data['zone_id'] <= 0) {
            return ['msg'=>$this->language->get('error_zone')];
        }else{
            //判断地址是否正确
            $this->load->model('localisation/zone');
            $info        = $this->model_localisation_zone->getZone($req_data['zone_id']);
            if (empty($info) || !isset($info['zone_id']) || (int)$info['zone_id'] <= 0) {
                return ['msg'=>$this->language->get('error_zone')];
            }
        }

        if ((int)$req_data['city_id'] <= 0) {
            return ['msg'=>$this->language->get('error_city')];
        }else{
            $this->load->model('localisation/city');
            $info        = $this->model_localisation_city->getCity($req_data['city_id']);
            if (empty($info) || (int)$info['zone_id'] != (int)$req_data['zone_id'] || (int)$info['up_id'] != 0) {
                return ['msg'=>$this->language->get('error_city')];
            }
        }

        if ((int)$req_data['county_id'] <= 0) {
            return ['msg'=>$this->language->get('error_county')];
        }else{
            $this->load->model('localisation/city');
            $info        = $this->model_localisation_city->getCity($req_data['county_id']);
            if (empty($info) || (int)$info['up_id'] != (int)$req_data['city_id']) {
                return ['msg'=>$this->language->get('error_city')];
            }
        }

        if ((utf8_strlen(trim($req_data['address'])) < 2) || (utf8_strlen(trim($req_data['address'])) > 40)) {
            return ['msg'=>$this->language->get('error_address')];
        }

        if ((utf8_strlen($req_data['store_name']) < 3) || (utf8_strlen($req_data['store_name']) > 32)) {
            return ['msg'=>$this->language->get('error_store_name')];
        }

        if ((utf8_strlen($req_data['experience']) < 1) || (utf8_strlen($req_data['experience']) > 40)) {
            return ['msg'=>$this->language->get('error_experience')];
        }

        if (!in_array((int)$req_data['company_type'], [1,2,3,4])) {
            return ['msg'=>$this->language->get('error_company_type')];
        }
        
        if ((utf8_strlen($req_data['company']) < 3) || (utf8_strlen($req_data['company']) > 32)) {
            return ['msg'=>$this->language->get('error_company')];
        }

        if ((utf8_strlen($req_data['license']) < 15) || (utf8_strlen($req_data['license']) > 20)) {
            return ['msg'=>$this->language->get('error_license')];
        }

        if ((utf8_strlen(trim($req_data['legal_person'])) < 2) || (utf8_strlen(trim($req_data['true_name'])) > 10)) {
            return ['msg'=>$this->language->get('error_legal_person')];
        }

        if (!(is_idcard($req_data['idnum']))) {
            return ['msg'=>$this->language->get('error_idnum')];
        }

        $images         = !empty($req_data['images']) ? explode(',', $req_data['images']) : [];
        if ( count($images) != 4) {
            return ['msg'=>$this->language->get('error_images')];
        }

        foreach ($images as $file) {
            if (!image_exists($file)) return ['msg'=>$this->language->get('error_images')];
        }

        return ['code'=>'200'];
    }
}
