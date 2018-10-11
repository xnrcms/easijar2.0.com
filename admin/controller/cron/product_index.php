<?php
/**
 * ControllerCronProductIndex.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-04-16 11:13
 * @modified   2018-04-16 11:13
 */


class ControllerCronProductIndex extends Controller
{
    public function index()
    {
        $this->load->model('catalog/product');
        $this->load->model('catalog/product_index');
        $this->load->model('customer/customer_group');

        $ratings = $this->model_catalog_product_index->getRatings();
        $sales = $this->model_catalog_product_index->getSales();
        $discounts = $this->model_catalog_product_index->getDiscounts();
        $specials = $this->model_catalog_product_index->getSpecials();

        $indexProducts = $this->registry->get('index_products', []);
        $customerGroups = $this->model_customer_customer_group->getCustomerGroups();
        $this->model_catalog_product_index->clearIndex();
        foreach ($indexProducts as $productId) {
            foreach ($customerGroups as $customerGroup) {
                $customerGroupId = $customerGroup['customer_group_id'];
                $specialKey = "{$productId}-{$customerGroupId}";
                $rating = array_get($ratings, $productId);
                $sale = array_get($sales, $productId);
                $discount = array_get($discounts, $specialKey);
                $special = array_get($specials, $specialKey);

                if ($rating || $sale || $discount || $special) {
                    $data = [
                        'product_id' => $productId,
                        'customer_group_id' => $customerGroupId,
                        'rating' => $rating,
                        'sale' => $sale,
                        'discount' => $discount,
                        'special' => $special
                    ];
                    $this->model_catalog_product_index->addIndex($data);
                }
            }
        }
    }
}
