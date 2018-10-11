<?php
class ModelNotifySms extends Model {
    public function send($telephone, $params = array(), $msg_template = '') {
        $find = array();
        $keys = array_keys($params);

        foreach($keys as $key) {
            $find[] = '{' . $key . '}';
        }

        return (new Sms())->setParams(trim(str_replace($find, $params, $msg_template)), $telephone)->send();
    }
}