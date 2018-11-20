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

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $this->load->model('account/customer');

        $validate       = $this->register_validate($req_data);

        if ( isset($validate['code']) && $validate['code'] == '201' ) {
            return $this->response->setOutput($this->returnData($validate));
        }

        if ( !(isset($validate['code']) && $validate['code'] == '200') ) {
            return $this->response->setOutput($this->returnData($validate));
        }

        $req_data['email']          = $req_data['account'];
        $req_data['fullname']       = '';
        $req_data['custom_field']   = '';
        $req_data['newsletter']     = 0;
        $req_data['safe']           = 0;
        $req_data['status']         = 0;

        $customer_id = $this->model_account_customer->addCustomer($req_data);

        unset($this->session->data['smscode']);

        $this->load->model('multiseller/seller');
        $this->model_multiseller_seller->saveSeller($customer_id, $req_data);

        // Clear any previous login attempts for unregistered accounts.
        if (!$this->config->get('module_multiseller_seller_approval')) {
            // Clear any previous login attempts in not registered.
            $this->model_account_customer->deleteLoginAttempts($customer_id);
        }

        return $this->response->setOutput($this->returnData(['code'=>'200','msg'=>'success','data'=>'register success']));
    }

    private function register_validate($req_data = [])
    {
        if ( !array_get($req_data, 'account') || ((utf8_strlen($req_data['account']) > 96) || !filter_var($req_data['account'], FILTER_VALIDATE_EMAIL))) {
            return ['msg'=>$this->language->get('error_email')];
        }

        if (array_get($req_data, 'account') && $this->model_account_customer->getTotalCustomersByEmail($req_data['account'])) {
            return ['code'=>'201','msg'=>$this->language->get('error_exists')];
        }

        if ((utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
            return ['msg'=>$this->language->get('error_password')];
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

        $allowKey       = ['api_token','true_name','zone_id','city_id','county_id','address','store_name','experience','company_type','company','license','legal_person','idnum','images'];
        $req_data       = $this->dataFilter($allowKey);
        $data           = $this->returnData();

        if (!$this->checkSign($req_data)) {
            return $this->response->setOutput($this->returnData(['msg'=>'fail:sign error']));
        }

        if (!(isset($this->session->data['api_id']) && (int)$this->session->data['api_id'] > 0)) {
            return $this->response->setOutput($this->returnData(['code'=>'203','msg'=>'fail:token is error']));
        }

        $validate       = $this->perfect_validate($req_data);

        if ( !(isset($validate['code']) && $validate['code'] == '200') ) {
            return $this->response->setOutput($this->returnData($validate));
        }

    }

    private function perfect_validate($req_data = [])
    {
        if ((utf8_strlen(trim($req_data['true_name'])) < 1) || (utf8_strlen(trim($req_data['true_name'])) > 10)) {
            return ['msg'=>$this->language->get('error_true_name')];
        }

        if (utf8_strlen($req_data['telephone']) != 11 || !is_mobile($req_data['telephone'])) {
            return ['msg'=>$this->language->get('error_telephone')];
        }

        if ($this->model_account_customer->getTotalCustomersByTelephone($req_data['telephone'])) {
            return ['msg'=>$this->language->get('error_exists_telephone')];
        }

        $keys                                       = md5('smscode-' . $req_data['account'] . '-1');
        if ((utf8_strlen(html_entity_decode($req_data['verification_code'], ENT_QUOTES, 'UTF-8')) != 6) || !isset($this->session->data['smscode'][$keys]) || $req_data['verification_code'] != $this->session->data['smscode'][$keys]['code'] ||  $this->session->data['smscode'][$keys]['expiry_time'] < time()){
            return ['msg'=>$this->language->get('error_smscode')];
        }

        if ((int)$req_data['zone_id'] <= 0) {
            return ['msg'=>$this->language->get('error_zone')];
        }

        if ((int)$req_data['city_id'] <= 0) {
            return ['msg'=>$this->language->get('error_city')];
        }

        if ((int)$req_data['county_id'] <= 0) {
            return ['msg'=>$this->language->get('error_county')];
        }

        if ((utf8_strlen(trim($req_data['address'])) < 2) || (utf8_strlen(trim($req_data['address'])) > 40)) {
            return ['msg'=>$this->language->get('error_true_name')];
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

        if ((utf8_strlen(trim($req_data['legal_person'])) < 1) || (utf8_strlen(trim($req_data['true_name'])) > 10)) {
            return ['msg'=>$this->language->get('error_legal_person')];
        }

        if (!(is_idcard($req_data['idnum']))) {
            return ['msg'=>$this->language->get('error_idnum')];
        }

        $images         = !empty($req_data['images']) ? explode(',', $req_data['images']) : [];
        if ( count($images) != 4) {
            return ['msg'=>$this->language->get('error_images')];
        }

        if (array_get($req_data, 'account') && $this->model_account_customer->getTotalCustomersByEmail($req_data['account'])) {
            return ['code'=>'201','msg'=>$this->language->get('error_exists')];
        }

        if ((utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($req_data['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
            return ['msg'=>$this->language->get('error_password')];
        }

        if ($req_data['confirm'] !== $req_data['password']) {
            return ['msg'=>$this->language->get('error_confirm')];
        }

        return ['code'=>'200'];
    }
}