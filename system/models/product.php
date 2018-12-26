<?php
/**
 * product.php
 *
 * @copyright  2018 opencart.cn - All Rights Reserved
 * @link       http://www.guangdawangluo.com
 * @author     Edward Yang <yangjin@opencart.cn>
 * @created    2018-05-07 17:12
 * @modified   2018-05-07 17:12
 */

namespace Models;

use Carbon\Carbon;
use Models\Product\Description;
use Models\Variant\Group;

class Product extends Base
{
    const USE_ALL_VARIANT_VALUES = false;
    protected $table = 'product';
    protected $primaryKey = 'product_id';

    public function descriptions()
    {
        return $this->hasMany(Description::class, 'product_id', 'product_id');
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id', 'product_id');
    }

    public function children()
    {
        return $this->hasMany(Product::class, 'parent_id', 'product_id');
    }


    public function saveVariants($data)
    {
        $requestProductIds = [];
        $childProductIds = $this->children()->pluck('product_id')->toArray();
        $childProductIds[] = $this->product_id;
        $requestProducts = array_get($data, 'products');

        if (empty($requestProducts)) {
            $removeProductIds = $childProductIds;
        } else {
            foreach ($data['products'] as $item) {
                $productId = $item['product_id'];
                if ($productId && in_array($productId, $childProductIds)) {
                    $requestProductIds[] = $productId;
                    $product = self::find($productId);
                    $product->updateVariant($item);
                } elseif ($productId == 0) {
                    self::createNewVariant($this->product_id, $item);
                }
            }
            $removeProductIds = array_diff($childProductIds, $requestProductIds);
        }
        if ($removeProductIds) {
            self::removeVariant($this->product_id, $removeProductIds);
        }
    }

    private function updateVariant($data)
    {
        $this->updateProduct($data);
        $variants = array_get($data, 'variants');
        if (empty($variants)) {
            return;
        }
        $this->variants()->delete();
        foreach ($variants as $variantId => $variantValueId) {
            if (!$variantId || !$variantValueId) {
                continue;
            }
            $this->variants()->create(array(
                'variant_id' => $variantId,
                'variant_value_id' => $variantValueId
            ));
        }
    }

    private function updateProduct($data)
    {
        $this->price = $data['price'];
        $this->quantity = $data['quantity'];
        $this->sku = $data['sku'];
        $this->status = $data['status'];
        if ($parentId = array_get($data, 'parent_id')) {
            $this->parent_id = $parentId;
        }
        $this->saveOrFail();
    }

    public function variants()
    {
        return $this->hasMany(Product\Variant::class)
            ->join('variant as v', 'v.variant_id', '=', 'product_variant.variant_id')
            ->join('variant_value as vv', 'vv.variant_value_id', '=', 'product_variant.variant_value_id')
            ->orderBy('v.sort_order')
            ->orderBy('vv.sort_order');
    }

    public static function createNewVariant($masterId, $data)
    {
        $ocProduct = model('catalog/product');
        $newProductId = $ocProduct->copyProduct($masterId);
        $newProduct = self::find($newProductId);
        $data['parent_id'] = $masterId;
        $newProduct->updateProduct($data);

        $variants = array_get($data, 'variants');
        if (empty($variants)) {
            return;
        }
        foreach ($variants as $variantId => $variantValueId) {
            if (!$variantId || !$variantValueId) {
                continue;
            }
            $newProduct->variants()->create(array(
                'variant_id' => $variantId,
                'variant_value_id' => $variantValueId
            ));
        }
    }

    public static function removeVariant($masterId, $productIds)
    {
        if (empty($productIds)) {
            return;
        }
        $ocProduct = model('catalog/product');
        foreach ($productIds as $productId) {
            $product = self::find($productId);
            $product->variants()->delete();
            if ($masterId != $productId) {
                $ocProduct->deleteProduct($productId);
            }
        }
    }

    public function getVariantForAdmin()
    {
        $masterProduct = $this->getMaster();
        $variants = [];
        $allVariants = Variant::getVariantValues();
        foreach ($allVariants as $variant) {
            $item = array(
                'id' => $variant->variant_id,
                'name' => $variant->name,
                'values' => $variant->getValueData()
            );
            $variants[] = $item;
        }

        $data['variants'] = $variants;
        $data['selected_variants'] = $masterProduct->getVariantIds()->map(function ($item) {
            return (int)$item;
        })->toArray();
        $data['products'] = $masterProduct->getChildrenProducts();
        $data['variant_groups'] = Group::getAllGroups();
        return $data;
    }

    public function getMaster()
    {
        if ($this->parent_id) {
            $masterProduct = $this->parent;
            if (empty($masterProduct)) {
                throw new \Exception("Invalid master product {$this->parent_id}");
            }
        } else {
            $masterProduct = $this;
        }
        return $masterProduct;
    }

    public function getVariantIds()
    {
        return $this->variants()
            ->groupBy('product_variant.variant_id')
            ->pluck('product_variant.variant_id');
    }

    private function getChildrenProducts()
    {
        $products[] = array(
            'product_id' => $this->product_id,
            'variants' => $this->getProductVariants(),
            'sku' => $this->sku,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'status' => $this->status
        );
        if ($this->children->count() == 0) {
            return $products;
        }

        foreach ($this->children as $child) {
            $childProduct = array(
                'product_id' => $child->product_id,
                'variants' => $child->getProductVariants(),
                'sku' => $child->sku,
                'price' => $child->price,
                'quantity' => $child->quantity,
                'status' => $child->status
            );
            $products[] = $childProduct;
        }
        return $products;
    }

    private function getProductVariants()
    {
        $variants = $this->variants()
            ->groupBy('product_variant.variant_id')
            ->get();
        $result = [];

        foreach ($variants as $variant) {
            $result[$variant->variant_id] = (int)$variant->variant_value_id;
        }
        return (object)$result;
    }

    public function getVariantLabels($type = 'array')
    {
        $labels = $labelData = [];
        foreach ($this->variants as $productVariant) {
            $variantName = $productVariant->variantName();
            $variantValueName = $productVariant->variantValueName();
            if (empty($variantValueName)) {
                continue;
            }
            $labels[] = $variantValueName;
            $labelData[] = array(
                'product_variant_id' => $productVariant->product_variant_id,
                'variant_id' => $productVariant->variant_id,
                'variant_value_id' => $productVariant->variant_value_id,
                'name' => $variantName,
                'value' => $variantValueName,
                'type' => 'variant'
            );
        }
        if ($type == 'array') {
            return $labelData;
        } elseif ($type == 'string') {
            return $labels ? implode('/', $labels) : '';
        }
    }

    public function getChildrenIds()
    {
        $masterProduct = $this->getMaster();
        if (empty($masterProduct)) {
            return [];
        }
        $productIds = $masterProduct->children()->pluck('product_id');
        return array_merge($productIds->toArray(), [$masterProduct->product_id]);
    }

    public function getProductVariantsDetail()
    {
        $productVariants = $this->variants;
        $currentKey = "|";
        $currentVariantValuesMap = array();
        foreach ($productVariants as $productVariant) {
            $currentKey .= "{$productVariant->variant_id}:{$productVariant->variant_value_id}|";
            $currentVariantValuesMap[$productVariant->variant_id] = $productVariant->variant_value_id;
        }

        $data['variants'] = $this->formatVariants();
        $data['product_variants'] = $currentVariantValuesMap;
        $data['keys'] = $currentKey;
        $data['skus'] = $this->getRelatedSKUs();
        return $data;
    }

    public function formatVariants()
    {
        $variants = [];
        $childrenIds = $this->getChildrenIds();
        if (empty($childrenIds)) {
            return [];
        }

        $productVariants = Product\Variant::withDescription($childrenIds)
            ->groupBy('product_variant.variant_value_id')
            ->get();

        foreach ($productVariants as $productVariant) {
            $variantId = $productVariant->variant_id;
            if (self::USE_ALL_VARIANT_VALUES) {
                $values[$variantId] = $productVariant->variant->getValueData();
            } else {
                $values[$variantId][] = $productVariant->formatValue();
            }
            $variants[$variantId] = array(
                'variant_id' => $variantId,
                'name' => $productVariant->variantName(),
                'values' => $values[$variantId]
            );
        }
        return $variants;
    }

    public function getRelatedSKUs()
    {
        $skus = [];
        $masterProduct = $this->getMaster();

        $skuKey = $this->getVariantKeys();
        $skus[$skuKey] = html_entity_decode(registry('url')->link('product/product', "product_id={$this->product_id}"));

        if ($masterProduct->isActive()) {
            $skuKey = $masterProduct->getVariantKeys();
            $skus[$skuKey] = html_entity_decode(registry('url')->link('product/product', "product_id={$masterProduct->product_id}"));
        }

        foreach ($masterProduct->children as $child) {
            if (!$child->isActive()) {
                continue;
            }
            $skuKey = $child->getVariantKeys();
            $skus[$skuKey] = html_entity_decode(registry('url')->link('product/product', "product_id={$child->product_id}"));
        }
        return $skus;
    }

    public function getVariantKeys()
    {
        $currentKey = "|";
        foreach ($this->variants as $productVariant) {
            $currentKey .= "{$productVariant->variant_id}:{$productVariant->variant_value_id}|";
        }
        return $currentKey;
    }

    public function isActive()
    {
        return $this->status && ($this->date_available < Carbon::now());
    }

    public function saveVariantDescriptions($descriptions)
    {
        $masterProduct = $this->getMaster();
        if (empty($masterProduct)) {
            return false;
        }

        $variantProductIds = $masterProduct->children->pluck('product_id')->toArray();
        if (!$this->isMaster()) {
            $variantProductIds[] = $masterProduct->product_id;
        }

        foreach ($variantProductIds as $variantProductId) {
            foreach ($descriptions as $languageId => $value) {
                $existDesc = Description::where('product_id', $variantProductId)
                    ->where('language_id', $languageId);
                if (empty($existDesc->count())) {
                    continue;
                }
                $existDesc->update([
                    //'name' => $value['name'],
                    'description' => $value['description']
                ]);
            }
        }
    }

    public function isMaster()
    {
        $masterProduct = $this->getMaster();
        if ($masterProduct && $masterProduct->product_id == $this->product_id) {
            return true;
        }
        return false;
    }
}
