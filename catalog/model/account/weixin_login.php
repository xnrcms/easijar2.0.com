<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2016-12-13 17:04:00
 * @modified         2016-12-13 17:04:00
 */

class ModelAccountWeixinLogin extends Model
{
    public function getCustomerByOpenid($openid)
    {
        $this->load->model('extension/module/social');
        return $this->model_extension_module_social->getCustomerByUid($openid, 'weixin_gz');
    }

    public function getCustomerByUnionid($unionid)
    {
        $this->load->model('extension/module/social');
        return $this->model_extension_module_social->getCustomerByUnionId($unionid, 'weixin_gz');
    }

    public function addWeixinCustomer($userinfo)
    {
        $customer_group_id = $this->config->get('config_customer_group_id');

        $this->load->model('account/customer_group');
        $this->load->model('account/customer');

        $data = array(
            'customer_group_id' => (int)$customer_group_id,
            'fullname' => $userinfo['nickname'],
            'email' => '', //md5($openid),
            'telephone' => '',
            'fax' => '',
            'password' => uniqid(rand(), true),
            'company' => '',
            'from'    => 'weixin_gz'
        );
        $customerId = $this->model_account_customer->addCustomer($data);

        $this->bindCustomer($customerId, $userinfo);

        return $customerId;
    }

    public function bindCustomer($customer_id, $userinfo)
    {
        $this->db->query("DELETE FROM " . DB_PREFIX . "customer_authentication WHERE uid = '" . trim($userinfo['openid']) . "'");

        $sql = "INSERT INTO " . DB_PREFIX . "customer_authentication (`customer_id`, `uid`, `unionid`, `provider`, `access_token`, `date_added`, `date_modified`) VALUES ('{$customer_id}', '{$userinfo['openid']}', '{$userinfo['unionid']}', 'weixin_gz', '{$userinfo['access_token']}', NOW(), NOW())";
        $this->db->query($sql);

        $this->updateCustomerAvatar($customer_id, $userinfo['headimgurl']);
    }

    public function updateCustomerAvatar($customer_id, $headimgurl)
    {
        $this->load->model('tool/image');
        $this->model_tool_image->getImage($headimgurl, DIR_IMAGE . 'avatar/', $customer_id . '.jpg');
    }
}
