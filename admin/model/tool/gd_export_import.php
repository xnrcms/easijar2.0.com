<?php
/**
 * @copyright        2016 opencart.cn - All Rights Reserved
 * @link             http://www.guangdawangluo.com
 * @author           TL <mengwb@opencart.cn>
 * @created          2017-12-26 11:22:04
 * @modified         2017-12-26 17:37:17
 */

class ModelToolGdExportImport extends Model {
    private $error = array();
    private $min_product_id = 0;
    private $max_product_id = 0;
    private $exportway = '';
    private $page = 1;
    private $count_prepage = 10;
    private $upload_product_ids = array();
    // pre-define some commonly used styles
    private $format = array(
        'text' => array(
            'font' => array(
                'name' => 'Arial',
                'size' => '10',
            ),
            'numberformat' => array(
                'code' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT
            ),
            'alignment' => array(
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrap'       => false,
                'indent'     => 0
            )
        )
    );

    //定义$template每个sheet对应的id，改id仅仅是为了校验语言，即该sheet中同一个id的出现的次数必须等于oc系统的语言数，当该sheet不需要校验语言完整性的时候则传空值
    private $template_sheets_id = array(
        'base'    => array(
            'categories'       => 'category_id',
            'filter_groups'    => 'filter_group_id',
            'filters'          => 'filter_id',
            'variants'         => 'variant_id',
            'variant_values'   => 'variant_value_id',
            'options'          => 'option_id',
            'option_values'    => 'option_value_id',
            'customer_groups'  => 'customer_group_id',
            'attribute_groups' => 'attribute_group_id',
            'attributes'       => 'attribute_id'
        ),
        'product' => array(
            'products'             => '',
            'product_descriptions' => 'product_id',
            'product_images'       => '',
            'product_variants'     => '',
            'product_options'      => '',
            'product_attributes'   => '',
            'product_specials'     => '',
            'product_discounts'    => '',
            'product_rewards'      => ''
        )
    );
    private $template = array(
        'base' => array(
            'categories' => array(
                array('key'    => 'category_id',      'value'  => 'Category ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'parent_id',        'value'  => 'Parent ID',       'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'filters',          'value'  => 'Filters',         'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',            'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'top',              'value'  => 'Top',             'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'bool'),
                array('key'    => 'column',           'value'  => 'Column',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'image',            'value'  => 'images',          'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'date_added',       'value'  => 'Date Added',      'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'datetime'),
                array('key'    => 'date_modified',    'value'  => 'Date Modified',   'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'datetime'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'description',      'value'  => 'Description',     'cell_width' => 38,'format' => 'text', 'require' => false,  'type' => 'html'),
                array('key'    => 'meta_title',       'value'  => 'Meta Title',      'cell_width' => 18,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'meta_description', 'value'  => 'Meta Description','cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'meta_keyword',     'value'  => 'Meta Keyword',    'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'status',           'value'  => 'Status',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'bool')
            ),
            'filter_groups' => array(
                array('key'    => 'filter_group_id',  'value'  => 'Filter Group ID', 'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',            'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'filters' => array(
                array('key'    => 'filter_id',        'value'  => 'Filter ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'filter_group_id',  'value'  => 'Filter Group ID', 'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',            'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'variants' => array(
                array('key'    => 'variant_id',       'value'  => 'Variant ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',            'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'variant_values' => array(
                array('key'    => 'variant_value_id', 'value'  => 'Variant Value ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'variant_id',       'value'  => 'variant ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Value',           'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'image',            'value'  => 'Image',           'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'options' => array(
                array('key'    => 'option_id',        'value'  => 'Option ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',            'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'type',             'value'  => 'Type',            'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'option_values' => array(
                array('key'    => 'option_value_id',  'value'  => 'Option Value ID', 'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'option_id',        'value'  => 'Option ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Value',           'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'image',            'value'  => 'Image',           'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'customer_groups' => array(
                array('key'    => 'customer_group_id','value'  => 'Customer Group ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',             'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'description',      'value'  => 'Description',      'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'html')
            ),
            'attribute_groups' => array(
                array('key'    => 'attribute_group_id','value'  => 'Attribute Group ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',       'value'  => 'Language ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',              'value'  => 'Name',              'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',        'value'  => 'Sort Order',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'attributes' => array(
                array('key'    => 'attribute_id',      'value'  => 'Attribute ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'attribute_group_id','value'  => 'Attribute Group ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',       'value'  => 'Language ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',              'value'  => 'Name',              'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',        'value'  => 'Sort Order',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            )
        ),
        'product' => array(
            'products' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'parent_id',        'value'  => 'Parent ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'categorys',        'value'  => 'Categories',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'filters',          'value'  => 'Filters',         'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'sku',              'value'  => 'SKU',             'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'location',         'value'  => 'Location',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'quantity',         'value'  => 'Quantity',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'model',            'value'  => 'Model',           'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'image',            'value'  => 'Image',           'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'shipping',         'value'  => 'Require Shipping','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'bool'),
                array('key'    => 'price',            'value'  => 'Price',           'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'points',           'value'  => 'Points',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'date_added',       'value'  => 'Date Added',      'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'datetime'),
                array('key'    => 'date_modified',    'value'  => 'Date Modified',   'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'datetime'),
                array('key'    => 'date_available',   'value'  => 'Date Available',  'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'datetime'),
                array('key'    => 'weight',           'value'  => 'Weight',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'weight_class_id',  'value'  => 'Weight Unit',     'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'length',           'value'  => 'Length',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'width',            'value'  => 'Width',           'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'height',           'value'  => 'Height',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'length_class_id',  'value'  => 'Length Unit',     'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'tax_class_id',     'value'  => 'Tax Class ID',    'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'viewed',           'value'  => 'Viewed',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'stock_status_id',  'value'  => 'Stock Status ID', 'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'relateds',         'value'  => 'Related IDs',     'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'subtract',         'value'  => 'Subtract',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'minimum',          'value'  => 'Minimum',         'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'status',           'value'  => 'Status',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'bool')
            ),
            'product_descriptions' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'name',             'value'  => 'Name',            'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'html'),
                array('key'    => 'description',      'value'  => 'Description',     'cell_width' => 38,'format' => 'text', 'require' => false,  'type' => 'html'),
                array('key'    => 'meta_title',       'value'  => 'Meta Title',      'cell_width' => 18,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'meta_description', 'value'  => 'Meta Description','cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'meta_keyword',     'value'  => 'Meta Keyword',    'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'tag',              'value'  => 'Tags',            'cell_width' => 28,'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'product_images' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'image',            'value'  => 'Image',           'cell_width' => 28,'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'sort_order',       'value'  => 'Sort Order',      'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'product_variants' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'variant_id',       'value'  => 'Variant ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'variant_value_id', 'value'  => 'Variant Value ID','cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
            ),
            'product_options' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'option_id',        'value'  => 'Option ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'option_value_id',  'value'  => 'Option Value ID', 'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'value',            'value'  => 'Value',           'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'required',         'value'  => 'Required',        'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'quantity',         'value'  => 'Quantity',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'subtract',         'value'  => 'Subtract',        'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'bool'),
                array('key'    => 'price',            'value'  => 'Price',           'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'price_prefix',     'value'  => 'Price Prefix',    'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'points',           'value'  => 'Points',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'points_prefix',    'value'  => 'Points Prefix',   'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'weight',           'value'  => 'Weight',          'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'weight_prefix',    'value'  => 'Weight Prefix',   'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal')
            ),
            'product_attributes' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',      'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'attribute_id',     'value'  => 'Attribute ID',    'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'language_id',      'value'  => 'Language ID',     'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'text',             'value'  => 'Text',            'cell_width' => 18,'format' => 'text', 'require' => true,   'type' => 'normal'),
            ),
            'product_specials' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'customer_group_id','value'  => 'Customer Group ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'priority',         'value'  => 'Priority',         'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'price',            'value'  => 'Price',            'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'date_start',       'value'  => 'Date Start',       'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'date'),
                array('key'    => 'date_end',         'value'  => 'Date End',         'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'date'),
            ),
            'product_discounts' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'customer_group_id','value'  => 'Customer Group ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'quantity',         'value'  => 'Quantity',         'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'priority',         'value'  => 'Priority',         'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'price',            'value'  => 'Price',            'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
                array('key'    => 'date_start',       'value'  => 'Date Start',       'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'date'),
                array('key'    => 'date_end',         'value'  => 'Date End',         'cell_width' => 18,'format' => 'text', 'require' => false,  'type' => 'date'),
            ),
            'product_rewards' => array(
                array('key'    => 'product_id',       'value'  => 'Product ID',       'cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'customer_group_id','value'  => 'Customer Group ID','cell_width' => 8, 'format' => 'text', 'require' => true,   'type' => 'normal'),
                array('key'    => 'points',           'value'  => 'Points',           'cell_width' => 8, 'format' => 'text', 'require' => false,  'type' => 'normal'),
            ),
        )
    );

    function upload($filename, $type = 'base') {
        $this->session->data['export_nochange'] = 1;

        // parse uploaded spreadsheet file
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filename);
        $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $objReader->setReadDataOnly(true);
        $reader = $objReader->load($filename);

        // read the various worksheets and load them to the database
        $ok = $this->validateUpload($reader, $type);
        if (!$ok) {
            return (implode('<br/>', $this->error));
        }
        $this->cache->delete('*');
        $this->session->data['export_nochange'] = 0;

        $i = 0;
        foreach ($this->template[$type] AS $sheet_name => $value) {
            $sheet_data = $this->readData($reader, $i++, $sheet_name, $type);
            $func = $this->convertUnderline('upload_' . $sheet_name);
            $this->{$func}($sheet_data);
        }
        return TRUE;
    }

    function validateUpload(&$reader, $type)
    {
        $template = $this->template[$type];
        $this->error = array();
        if ($reader->getSheetCount() != count($template)) {
            $this->error[] = $this->language->get('error_sheet_count');
        }

        if (!$this->error) {
            $i = 0;
            foreach ($template as $key => $item) {
                $data = $reader->getSheet($i);
                $expectedHeading = array_column($item, 'value');
                if (!$this->validateHeading($data, $expectedHeading)) {
                    $this->error[$key] = sprintf($this->language->get('error_header'), $key);
                }
                $i++;
            }
        }

        if (!$this->error) {
            $i = 0;
            foreach ($template AS $sheet_name => $value) {
                $sheet_data = $this->readData($reader, $i++, $sheet_name, $type);
                $this->validateSheet($sheet_data, $sheet_name, $this->template_sheets_id[$type][$sheet_name]);
            }
        }

        return !$this->error;
    }

    function validateHeading(&$data, &$expected) {
        $heading = array();
        $k = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString( $data->getHighestColumn() );
        if ($k != count($expected)) {
            return FALSE;
        }
        $i = 0;
        for ($j=1; $j <= $k; $j+=1) {
            $heading[] = $this->getCell($data,$i,$j);
        }
        $valid = TRUE;
        for ($i=0; $i < count($expected); $i+=1) {
            if (!isset($heading[$i])) {
                $valid = FALSE;
                break;
            }
            if (strtolower($heading[$i]) != strtolower($expected[$i])) {
                $valid = FALSE;
                break;
            }
        }
        return $valid;
    }

    function getCell(&$worksheet,$row,$col,$default_val='') {
        $row += 1; // we use 0-based, PHPExcel used 1-based row index
        return ($worksheet->cellExistsByColumnAndRow($col,$row)) ? $worksheet->getCellByColumnAndRow($col,$row)->getValue() : $default_val;
    }

    private function readData(&$reader, $sheet_index, $sheet_name, $type) {
        $data = $reader->getSheet($sheet_index);
        $result = array();
        $k = $data->getHighestRow();
        for ($i=1; $i<$k; $i++) {  // 第一行是表头，所以跳过$i=0的一行
            $j = 1;
            $item = array();
            foreach ($this->template[$type][$sheet_name] as $field_info) {
                $cell_value = trim($this->getCell($data, $i, $j++));
                if ($field_info['require'] && $cell_value === '') {
                    $this->error['required'] = sprintf($this->language->get('error_required'),$field_info['key'], $sheet_name, ($i + 1));
                }
                if ($field_info['type'] = 'html') {
                    $cell_value = htmlentities($cell_value, ENT_QUOTES, $this->detect_encoding($cell_value));
                } else if ($field_info['type'] = 'bool') {
                    $cell_value = ($cell_value == 1 || strtolower($cell_value) == 'true' || strtolower($cell_value) == 'yes' || $cell_value == '是' || strtolower($cell_value) == 't' || strtolower($cell_value) == 'y') ? true : false;
                } else if ($field_info['type'] = 'datetime') {
                    $cell_value = ((is_string($cell_value)) && (strlen($cell_value) > 0)) ? $cell_value : "NOW()";
                } else if ($field_info['type'] = 'date') { //主要针对各种开始结束日期，比如特价开始日期、特价结束日期
                    $cell_value = ((is_string($cell_value)) && (strlen($cell_value) > 0)) ? $cell_value : "0000-00-00";
                }
                $item[$field_info['key']] = $cell_value;
            }
            $result[] = $item;
        }
        return $result;
    }

    private function detect_encoding($str) {
        // auto detect the character encoding of a string
        return mb_detect_encoding( $str, 'UTF-8,ISO-8859-15,ISO-8859-1,cp1251,KOI8-R' );
    }

    private function validateSheet($sheet_data, $template_index, $id_field) {
        if (!$id_field) {
            return true;
        }
        $language_count = $this->getTotalLanguages();
        $id_array = array_column($sheet_data, $id_field);
        $id_counts = array_count_values($id_array);
        foreach ($id_counts as $id => $id_count) {
            if ($id_count != $language_count) {
                $this->error['language'] = sprintf($this->language->get('error_languages_count'),$template_index, $id_field, $id, $language_count);
                return false;
            }
        }
        return true;
    }

	private function getTotalLanguages() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "language");

		return $query->row['total'];
	}

    private function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        },$str);
        return $str;
    }

    function download($type, $post = array()) {
        // set appropriate timeout limit
        set_time_limit( 1800 );
        ini_set('memory_limit', '512M');

        // create a new workbook
        $workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // set default font name and size
        $workbook->getDefaultStyle()->getFont()->setName('Arial');
        $workbook->getDefaultStyle()->getFont()->setSize(10);
        $workbook->getDefaultStyle()->getAlignment()->setIndent(1);

        if (isset($this->request->post['exportway'])) {
            if ($this->request->post['exportway'] == 'pid') {
                $this->min_product_id = $this->request->post['min'];
                $this->max_product_id = $this->request->post['max'];
                $this->exportway = $this->request->post['exportway'];
            } else if ($this->request->post['exportway'] == 'page') {
                $this->count_prepage = $this->request->post['min'];
                $this->page = $this->request->post['max'];
                $this->exportway = $this->request->post['exportway'];
            } else {
                echo "exportway error!";
            }
        }

        // creating the worksheet
        $i = 0;
        foreach ($this->template[$type] as $key => $value) {
            if ($i > 0) {
                $workbook->createSheet();
            }
            $workbook->setActiveSheetIndex($i++);
            $worksheet = $workbook->getActiveSheet();
            $worksheet->setTitle($key);
            $func = $this->convertUnderline('get_' . $key);
            $this->populateWorksheet($worksheet, $this->{$func}(), $value);
            $worksheet->freezePaneByColumnAndRow( 1, 2 );
        }

        $workbook->setActiveSheetIndex(0);

        $datetime = date('Y-m-d');
        //header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="GDExportImport_products_'.$datetime.'.xls"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="GDExportImport_'.$type.'_'.$datetime.'.xlsx"');
        header('Cache-Control: max-age=0');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        //$writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xls');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($workbook, 'Xlsx');
        if(ob_get_length()){
            ob_clean();
        }

        $writer->save('php://output');
    }

    /**
     * @param $worksheet
     * @param $data 要写入工作表的数据， 每个记录中的字段顺序必须和$template一致
     * @param $template 工作表的表头，如：array(array('key'=>'product_id','name'=>'Product ID','format'=>'box'),array('key'=>'model','name'=>'Model  ','format'=>'box')),其中value的实际长度即为excel列的宽度，需要宽一点的直接加空格
     */
    private function populateWorksheet(&$worksheet, $data, $template)
    {
        // Set the column widths
        $j = 1;
        foreach ($template as $item) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth($item['cell_width']);
        }

        // The heading row
        $i = 1;
        $j = 1;
        foreach ($template as $item) {
            $this->setCell($worksheet, $i, $j++, $item['value'], $this->format[$item['format']]);
        }
        $worksheet->getRowDimension($i)->setRowHeight(30);

        // The actual product discounts data
        $i++;
        foreach ($data as $row) {
            $worksheet->getRowDimension($i)->setRowHeight(13);
            $j = 1;
            foreach ($template as $item) {
                $cell_value = $row[$item['key']];
                if ($item['type'] == 'html') {
                    $cell_value = html_entity_decode($cell_value,ENT_QUOTES,'UTF-8');
                }
                $this->setCell($worksheet, $i, $j++, $cell_value, $this->format[$item['format']]);
            }
            $i++;
        }
    }

    private function setCell(&$worksheet, $row/*1-based*/, $col/*0-based*/, $val, $style=NULL) {
        $worksheet->setCellValueByColumnAndRow($col, $row, $val);
        if ($style) {
            $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($style);
        }
    }

    protected function getProducts() {
        $query  = "SELECT DISTINCT ";
        $query .= "  p.*,";
        $query .= "  GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR \",\" ) AS categorys,";
        $query .= "  GROUP_CONCAT( DISTINCT CAST(pf.filter_id AS CHAR(11)) SEPARATOR \",\" ) AS filters,";
        $query .= "  m.name AS manufacturer,";
        $query .= "  wc.unit AS weight_unit,";
        $query .= "  mc.unit AS length_unit, ";
        $query .= "  GROUP_CONCAT( DISTINCT CAST(pr.related_id AS CHAR(11)) SEPARATOR \",\" ) AS relateds ";
        $query .= "FROM `".DB_PREFIX."product` p ";
        $query .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON p.product_id=pc.product_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."product_filter` pf ON p.product_id=pf.product_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = p.manufacturer_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."weight_class_description` wc ON wc.weight_class_id = p.weight_class_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."length_class_description` mc ON mc.length_class_id=p.length_class_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."product_related` pr ON pr.product_id=p.product_id ";
        $query .= "WHERE 1=1 ";
        if ($this->exportway == 'pid') {
            $query .= "AND p.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        }
        $query .= "GROUP BY p.product_id ";
        $query .= "ORDER BY p.product_id, pc.category_id, pf.filter_id ";
        if ($this->exportway == 'page') {
            $query .= "LIMIT " . ($this->page - 1) * $this->count_prepage . ", " . $this->count_prepage . ";";
        }
        $result = $this->db->query( $query );

        if ($this->exportway == 'page' && $result->rows) {
            $this->min_product_id = $result->rows[0]['product_id'];
            $this->max_product_id = $result->rows[count($result->rows) - 1]['product_id'];
        }
        return $result->rows;
    }

    protected function getProductDescriptions() {
        $query  = "SELECT * FROM `".DB_PREFIX."product_description` pd ";
        $query .= "WHERE pd.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pd.`product_id`, pd.`language_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    protected function getProductImages() {
        $query  = "SELECT * FROM `".DB_PREFIX."product_image` pi ";
        $query .= "WHERE pi.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pi.`product_id`, pi.`sort_order`, pi.`image`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    protected function getProductVariants() {
        $query = "SELECT * FROM `".DB_PREFIX."product_variant` pv ";
        $query .= "WHERE pv.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pv.product_id, pv.variant_id, pv.variant_value_id;";

        $result = $this->db->query($query);
        return $result->rows;
    }

    protected function getProductOptions() {
        $query  = "SELECT po.product_id,";
        $query .= "  po.option_id,";
        $query .= "  po.value,";
        $query .= "  po.required,";
        $query .= "  pov.product_option_value_id,";
        $query .= "  po.product_option_id,";
        $query .= "  pov.option_value_id,";
        $query .= "  pov.quantity,";
        $query .= "  pov.subtract,";
        $query .= "  pov.price,";
        $query .= "  pov.price_prefix,";
        $query .= "  pov.points,";
        $query .= "  pov.points_prefix,";
        $query .= "  pov.weight,";
        $query .= "  pov.weight_prefix ";
        $query .= "FROM `".DB_PREFIX."product_option` po ";
        $query .= "LEFT JOIN `".DB_PREFIX."product_option_value` pov ON pov.product_option_id = po.product_option_id ";
        $query .= "WHERE po.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY po.product_id, po.option_id, pov.option_value_id;";

        $result = $this->db->query( $query );
        return $result->rows;
    }

    protected function getProductAttributes()
    {
        $query  = "SELECT * FROM `".DB_PREFIX."product_attribute` pa ";
        $query .= "WHERE pa.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pa.`product_id`, pa.`attribute_id`, pa.`language_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    protected function getProductSpecials()
    {
        $query  = "SELECT * FROM `".DB_PREFIX."product_special` pa ";
        $query .= "WHERE pa.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pa.`product_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    protected function getProductDiscounts()
    {
        $query  = "SELECT * FROM `".DB_PREFIX."product_discount` pa ";
        $query .= "WHERE pa.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pa.`product_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    protected function getProductRewards()
    {
        $query  = "SELECT * FROM `".DB_PREFIX."product_reward` pa ";
        $query .= "WHERE pa.product_id BETWEEN ".$this->min_product_id." AND ".$this->max_product_id." ";
        $query .= "ORDER BY pa.`product_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function uploadProducts($products) {
        foreach ($products as $product) {
            if (!in_array($product['product_id'], $this->upload_product_ids)) {
                $this->upload_product_ids[] = $product['product_id'];
            }
        }
        $result1 = $this->storeIntoDatabase($products, 'product', array('product_id'), array('product_id', 'parent_id', 'sku', 'location', 'quantity', 'model', 'image', 'shipping', 'price', 'points', 'sort_order', 'date_added', 'date_modified', 'date_available', 'weight', 'weight_class_id', 'length', 'width', 'height', 'length_class_id', 'tax_class_id', 'viewed', 'stock_status_id', 'sort_order', 'subtract', 'minimum', 'status'));
        $result2 = $this->storeIntoDatabaseFromImplode($products, 'product_to_category', 'product_id', 'categorys');
        $result3 = $this->storeIntoDatabaseFromImplode($products, 'product_filter', 'product_id', 'filters');
        $result4 = $this->storeIntoDatabaseFromImplode($products, 'product_related', 'product_id', 'relateds');
        $result5 = $this->storeIntoDatabaseFromImplode($products, 'product_to_store', 'product_id', 'stores');

        return ($result1 && $result2 && $result3 && $result4 && $result5);
    }

    /**
     * @param $data excel sheet中读取出来的完整数据
     * @param $table 字符串，存储的数据库表名
     * @param $uniques 数组，表$table的组合键，不一定是主键，所有字段必须包含在$fields中，程序会根据这几个字段来判断是更新记录还是插入
     * 记录，如果该字段传空array()，则全部插入，但插入前按$foreign_key值删除记录
     * @param $foreign_key 插入前按$foreign_key值删除记录
     * @param $fields 数组，要写入的表字段
     * @return bool
     */
    private function storeIntoDatabase($data, $table, $uniques, $fields, $foreign_key = '')
    {
        $existent_ids = array();  // 记录数据库已经存在的$uniques字段组合

        if ($uniques) {
            $query = $this->db->query("SELECT " . implode(', ', $uniques) . " FROM `" . DB_PREFIX . $table . "`");

            foreach ($query->rows as $value) {
                $existent_ids[] = implode('-', $value);
            }
        }

        $executed_ids = array(); // 记录$data中已经执行过的记录，$uniques组合作为索引，如果已经执行过，则不在执行。
        $already_deleted = array();
        foreach ($data as $item) {
            // 只有导入商品数据时$this->upload_product_ids才会有值，此时商品关联的其他表product_id字段不在$this->upload_product_ids中的记录应该当作多余数据忽略。
            if ($this->upload_product_ids && !in_array($item['product_id'], $this->upload_product_ids)) {
                continue;
            }

            if ($foreign_key) {
                if (!in_array($item[$foreign_key], $already_deleted)) {
                    $this->db->query("DELETE FROM `" . DB_PREFIX . $table . "` WHERE `" . $foreign_key . "` = '" . $item[$foreign_key] . "'");
                    $already_deleted[] = $item[$foreign_key];
                }
            }

            $ids = array();
            foreach ($uniques as $unique) {
                if (!$item[$unique]) { // 插入的字段中组合键字段为空则报错
                    continue;//throw new Exception("Error: The field '$unique' of table '$table' require!");
                }
                $ids[] = $item[$unique];
            }

            // 判断当前记录是否已经处理过，已处理则继续下一条记录，否则，标记该记录为已处理，然后处理。以$ids为唯一标记不重复处理
            if ($uniques) {
                if (in_array(implode('-', $ids), $executed_ids)) {
                    continue;
                } else {
                    $executed_ids[] = implode('-', $ids);
                }
            }

            // 以$ids为组合键的记录如果数据库存在则更新，否则插入
            if (in_array(implode('-', $ids), $existent_ids) && (!$foreign_key || !in_array($item[$foreign_key], $already_deleted))) {
                $sql = "UPDATE `" . DB_PREFIX . $table . "` SET";
            } else {
                $sql = "INSERT INTO `" . DB_PREFIX . $table . "` SET";
            }

            $first = true;
            foreach ($item as $key => $value) {
                if (in_array($key, $fields)){
                    if ($first) {
                        $sql .= " `" . $key . "`='" . $this->db->escape($value) . "'";
                        $first = false;
                    } else {
                        $sql .= ", `" . $key . "`='" . $this->db->escape($value) . "'";
                    }
                }
            }

            if ($uniques) {
                if (in_array(implode('-', $ids), $existent_ids) && (!$foreign_key || !in_array($item[$foreign_key], $already_deleted))) {
                    $sql .= " WHERE";
                    $first = true;
                    foreach ($uniques as $unique) {
                        if ($first) {
                            $first = false;
                            $sql .= " " . $unique . " = '" . $item[$unique] . "'";
                        } else {
                            $sql .= " AND " . $unique . " = '" . $item[$unique] . "'";
                        }
                    }
                }
            }
            $this->db->query($sql);
        }

        return TRUE;
    }

    /**
     * @param $data  要存储的数
     * @param $table  要存储到该数据库表
     * @param $id_field  关系组合键的除$implode_field的另一个字段, 该字段的值必须是表的字段名加s，不能是其他形式
     * @param $implode_field  关系组合键的一个字段，$data中该字段不存在的话默认为0
     * @return bool
     * @throws Exception
     * 用于存储只有两个字段的关系表
     */
    private function storeIntoDatabaseFromImplode($data, $table, $id_field, $implode_field) {
        $executed_ids = array(); // 记录$data中已经执行过的记录，$uniques组合作为索引，如果已经执行过，则不在执行。
        foreach ($data as $item) {
            if (!$item[$id_field]) { // 插入的字段中组合键字段为空则报错
                throw new Exception("Error: The field '$id_field' of table '$table' require!");
            }

            // 只有导入商品数据时$this->upload_product_ids才会有值，此时商品关联的其他表product_id字段不在$this->upload_product_ids中的记录应该当作多余数据忽略。
            if ($this->upload_product_ids && !in_array($item['product_id'], $this->upload_product_ids)) {
                continue;
            }

            // 判断当前记录是否已经处理过，已处理则继续下一条记录，否则，标记该记录为已处理，然后处理。以$ids为唯一标记不重复处理
            if (in_array($item[$id_field], $executed_ids)) {
                continue;
            } else {
                $executed_ids[] = $item[$id_field];
            }

            $this->db->query("DELETE FROM `" . DB_PREFIX . $table . "` WHERE `" . $id_field . "` = '" . $item[$id_field] . "'");

            $sql = "INSERT INTO `" . DB_PREFIX . $table . "` (`" . $id_field . "`, `" . mb_substr($implode_field, 0, mb_strlen($implode_field) - 1) . "_id`) VALUES ";

            if (isset($item[$implode_field])) {
                $implode_str = str_replace('，', ',', $item[$implode_field]); //去掉全角逗号
                $implode_values = explode(',', $implode_str);
            } else {
                $implode_values = array(0);
            }

            $first = TRUE;
            foreach ($implode_values as $implode_value) {
                $implode_value = (int)trim($implode_value);
                if ($implode_value || $table == 'product_to_store') {
                    $sql .= ($first) ? "\n" : ",\n";
                    $first = FALSE;
                    $sql .= "('" . (int)$item[$id_field] . "', '" . (int)$implode_value . "')";
                }
            }

            $first ?: $this->db->query($sql);
        }
        return TRUE;
    }

    private function uploadProductDescriptions($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_description', array('product_id', 'language_id'), array('product_id', 'language_id', 'name', 'description', 'meta_title', 'meta_description', 'meta_keyword', 'tag'), 'product_id');

        return ($result1);
    }

    private function uploadProductImages($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_image', array(), array('product_id', 'image', 'sort_order'), 'product_id');

        return ($result1);
    }

    private function uploadProductVariants($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_variant', array('product_id', 'variant_id'), array('product_id', 'variant_id', 'variant_value_id'), 'product_id');

        return ($result1);
    }

    private function uploadProductOptions($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_option', array('product_id', 'option_id'), array('product_id', 'option_id', 'value', 'required'), 'product_id');
        $result2 = $this->storeIntoDatabase($data, 'product_option_value', array('product_id', 'option_id', 'option_value_id'), array('product_id', 'option_id', 'option_value_id', 'quantity', 'price', 'price_prefix', 'points', 'points_prefix', 'weight', 'weight_prefix'), 'product_id');
        $this->repairProductOptions();

        return ($result1 && $result2);
    }

    private function repairProductOptions() {
        $sql = "UPDATE `" . DB_PREFIX . "product_option_value` pov SET pov.product_option_id = (SELECT po.product_option_id FROM `" . DB_PREFIX . "product_option` po WHERE po.product_id=pov.product_id AND po.option_id=pov.option_id)";
        $this->db->query($sql);
    }

    private function uploadProductAttributes($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_attribute', array(), array('product_id', 'attribute_id', 'language_id', 'text'), 'product_id');

        return ($result1);
    }

    private function uploadProductSpecials($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_special', array(), array('product_id', 'customer_group_id', 'priority', 'price', 'date_start', 'date_end'), 'product_id');

        return ($result1);
    }

    private function uploadProductDiscounts($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_discount', array(), array('product_id', 'customer_group_id', 'quantity', 'priority', 'price', 'date_start', 'date_end'), 'product_id');

        return ($result1);
    }

    private function uploadProductRewards($data) {
        $result1 = $this->storeIntoDatabase($data, 'product_reward', array(), array('product_id', 'customer_group_id', 'points'), 'product_id');

        return ($result1);
    }


    // 以下是获取product相关数据的方法

    private function uploadCategories($categories) {
        $result1 = $this->storeIntoDatabase($categories, 'category', array('category_id'), array('category_id', 'parent_id', 'top', 'column', 'sort_order', 'image', 'date_added', 'date_modified', 'status'));
        $result2 = $this->storeIntoDatabase($categories, 'category_description', array('category_id', 'language_id'), array('category_id', 'language_id', 'name', 'description', 'meta_title', 'meta_description', 'meta_keyword'), 'category_id');
        $result3 = $this->storeIntoDatabaseFromImplode($categories, 'category_filter', 'category_id', 'filters');
        // restore category paths for faster lookups on the frontend
        $this->load->model( 'catalog/category' );
        $this->model_catalog_category->repairCategories(0);

        return ($result1 && $result2 && $result3);
    }

    private function uploadFilterGroups($filter_groups) {
        $result1 = $this->storeIntoDatabase($filter_groups, 'filter_group', array('filter_group_id'), array('filter_group_id', 'sort_order'));
        $result2 = $this->storeIntoDatabase($filter_groups, 'filter_group_description', array('filter_group_id', 'language_id'), array('filter_group_id', 'language_id', 'name'), 'filter_group_id');

        return ($result1 && $result2);
    }

    private function uploadFilters($filters) {
        $result1 = $this->storeIntoDatabase($filters, 'filter', array('filter_id'), array('filter_id', 'filter_group_id', 'sort_order'));
        $result2 = $this->storeIntoDatabase($filters, 'filter_description', array('filter_id', 'language_id'), array('filter_id', 'filter_group_id', 'language_id', 'name'), 'filter_id');

        return ($result1 && $result2);
    }

    private function uploadVariants($options) {
        $result1 = $this->storeIntoDatabase($options, 'variant', array('variant_id'), array('variant_id', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'variant_description', array('variant_id', 'language_id'), array('variant_id', 'language_id', 'name'), 'variant_id');

        return ($result1 && $result2);
    }

    private function uploadVariantValues($options) {
        $result1 = $this->storeIntoDatabase($options, 'variant_value', array('variant_value_id'), array('variant_value_id', 'variant_id', 'image', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'variant_value_description', array('variant_value_id', 'language_id'), array('variant_value_id', 'variant_id', 'language_id', 'name'), 'variant_value_id');

        return ($result1 && $result2);
    }

    private function uploadOptions($options) {
        $result1 = $this->storeIntoDatabase($options, 'option', array('option_id'), array('option_id', 'type', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'option_description', array('option_id', 'language_id'), array('option_id', 'language_id', 'name'), 'option_id');

        return ($result1 && $result2);
    }

    private function uploadOptionValues($options) {
        $result1 = $this->storeIntoDatabase($options, 'option_value', array('option_value_id'), array('option_value_id', 'option_id', 'image', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'option_value_description', array('option_value_id', 'language_id'), array('option_value_id', 'option_id', 'language_id', 'name'), 'option_value_id');

        return ($result1 && $result2);
    }

    private function uploadCustomerGroups($options) {
        $result1 = $this->storeIntoDatabase($options, 'customer_group', array('customer_group_id'), array('customer_group_id', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'customer_group_description', array('customer_group_id', 'language_id'), array('customer_group_id', 'language_id', 'name', 'description'), 'customer_group_id');

        return ($result1 && $result2);
    }

    private function uploadAttributeGroups($options) {
        $result1 = $this->storeIntoDatabase($options, 'attribute_group', array('attribute_group_id'), array('attribute_group_id', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'attribute_group_description', array('attribute_group_id', 'language_id'), array('attribute_group_id', 'language_id', 'name'), 'attribute_group_id');

        return ($result1 && $result2);
    }

    private function uploadAttributes($options) {
        $result1 = $this->storeIntoDatabase($options, 'attribute', array('attribute_id'), array('attribute_id', 'attribute_group_id', 'sort_order'));
        $result2 = $this->storeIntoDatabase($options, 'attribute_description', array('attribute_id', 'language_id'), array('attribute_id', 'language_id', 'name'), 'attribute_id');

        return ($result1 && $result2);
    }

    private function getCategories() {
        $query  = "SELECT DISTINCT c.* , cd.*, GROUP_CONCAT( DISTINCT CAST(cf.filter_id AS CHAR(11)) SEPARATOR \",\" ) AS filters FROM `".DB_PREFIX."category` c ";
        $query .= "INNER JOIN `".DB_PREFIX."category_description` cd ON cd.category_id = c.category_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."seo_url` su ON su.query=CONCAT('category_id=',c.category_id) AND cd.language_id = su.language_id ";
        $query .= "LEFT JOIN `".DB_PREFIX."category_filter` cf ON c.category_id=cf.category_id ";
        $query .= "GROUP BY c.`category_id`, cd.`language_id` ";
        $query .= "ORDER BY c.`parent_id`, `sort_order`, c.`category_id`, cf.`filter_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getFilterGroups() {
        $query  = "SELECT fg.* , fgd.* FROM `".DB_PREFIX."filter_group` fg ";
        $query .= "INNER JOIN `".DB_PREFIX."filter_group_description` fgd ON fg.filter_group_id = fgd.filter_group_id ";
        $query .= "ORDER BY fg.`filter_group_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getFilters() {
        $query  = "SELECT f.sort_order , fd.* FROM `".DB_PREFIX."filter` f ";
        $query .= "INNER JOIN `".DB_PREFIX."filter_description` fd ON f.filter_id = fd.filter_id ";
        $query .= "ORDER BY f.`filter_id`;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getVariants() {
        $query  = "SELECT v.*, vd.language_id, vd.name ";
        $query .= "FROM `".DB_PREFIX."variant` v ";
        $query .= "LEFT JOIN `".DB_PREFIX."variant_description` vd ON vd.variant_id=v.variant_id ";
        $query .= "ORDER BY v.variant_id;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getVariantValues() {
        $query  = "SELECT vv.*, vvd.name, vvd.language_id ";
        $query .= "FROM `".DB_PREFIX."variant_value` vv ";
        $query .= "LEFT JOIN `".DB_PREFIX."variant_value_description` vvd ON vvd.variant_value_id=vv.variant_value_id ";
        $query .= "ORDER BY  vv.variant_id, vv.variant_value_id, vvd.language_id;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getOptions() {
        $query  = "SELECT o.option_id,";
        $query .= "  od.language_id,";
        $query .= "  o.sort_order,";
        $query .= "  od.name,";
        $query .= "  o.type ";
        $query .= "FROM `".DB_PREFIX."option` o ";
        $query .= "LEFT JOIN `".DB_PREFIX."option_description` od ON od.option_id=o.option_id ";
        $query .= "ORDER BY o.option_id;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getOptionValues() {
        $query  = "SELECT ov.*,";
        $query .= "  ovd.name,";
        $query .= "  ovd.language_id ";
        $query .= "FROM `".DB_PREFIX."option_value` ov ";
        $query .= "LEFT JOIN `".DB_PREFIX."option_value_description` ovd ON ovd.option_value_id=ov.option_value_id ";
        $query .= "ORDER BY  ov.option_id, ov.option_value_id, ovd.language_id;";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getCustomerGroups() {
        $query  = "SELECT cgd.* FROM `".DB_PREFIX."customer_group_description` cgd ";
        $query .= "ORDER BY cgd.customer_group_id";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getAttributeGroups() {
        $query  = "SELECT ag.*, agd.language_id, agd.name FROM `" . DB_PREFIX . "attribute_group` ag 
                   LEFT JOIN `" . DB_PREFIX . "attribute_group_description` agd ON agd.attribute_group_id = ag.attribute_group_id
                   ORDER BY ag.attribute_group_id";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getAttributes() {
        $query  = "SELECT a.*, ad.language_id, ad.name FROM `" . DB_PREFIX . "attribute` a 
                   LEFT JOIN `" . DB_PREFIX . "attribute_description` ad ON a.attribute_id = ad.attribute_id
                  ORDER BY a.attribute_id";
        $result = $this->db->query( $query );
        return $result->rows;
    }

    private function getDefaultLanguageId() {
        $query = $this->db->query("SELECT language_id FROM `".DB_PREFIX."language` WHERE code = '" . $this->config->get('config_language') . "' LIMIT 1");
        return $query->row['language_id'];
    }
}
