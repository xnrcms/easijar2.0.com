<?php

use Models\Faq\Category;

/**
 * description
 *
 * @copyright        2018/7/12 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           Eric Yang <yangyw@opencart.cn>
 * @created          2018/7/12 上午9:19
 * @modified         2018/7/12 上午9:19
 */
class ControllerInformationFaq extends Controller
{

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->language('information/faq');
    }

    public function index()
    {
        $this->document->setTitle(t('heading_title'));

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('information/faq')
        );

        $categories = Category::active()->get();

        $data['faqs'] = array();

        foreach ($categories as $category) {
            $faqs = $category->faqs()->get();

            if (count($faqs) > 0) {

                $question_list = array();
                foreach ($faqs as $faq) {
                    $question_list[] = array(
                        'faq_id' => $faq->faq_id,
                        'question' => $faq->localizedDescription()->question,
                        'answer' => html_entity_decode($faq->localizedDescription()->answer, ENT_QUOTES, 'UTF-8')
                    );
                }

                $data['faqs'][] = array(
                    'name' => $category->localizedDescription()->name,
                    'question_list' => $question_list
                );

            }
        }

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('information/faq', $data));
    }

}