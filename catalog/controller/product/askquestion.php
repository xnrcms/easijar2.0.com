<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <yangyw@opencart.cn>
 * @created          2016-11-10 11:12:00
 * @modified         2016-11-10 11:12:00
 */

class ControllerProductAskquestion extends Controller
{
    public function add()
    {
        $productId = array_get($this->request->post, 'product_id');
        if (!$productId) {
            return;
        }

        $name = array_get($this->request->post, 'name');
        if (!$name) {
            return 'Name required.';
        }

        $question = array_get($this->request->post, 'question');
        if (!$question) {
            return 'Question required.';
        }

        $this->load->model('catalog/askquestion');
        $this->load->language('product/askquestion');

        $status = 0;
        $this->model_catalog_askquestion->addQuestionAnswer($productId, $name, $question, $status);

        echo t('text_success');
    }
}
