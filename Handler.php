<?php

class Handler
{
    public $all_products = array();
    public $handled_products = array();
    public $unhandled_products = array();
    public $zeroing_products = array();

    public $parse_xml;

    public $uploadfile;
    public $count_up = 0;
    public $log = '';
    public $result;

    function __construct($uploadfile)
    {
        $args = array(
            'post_type' => array('product', 'product_variation'),
            'post_status' => array('private', 'publish'),
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'asc',
        );
        $this->all_products = get_posts($args);
        $this->uploadfile = $uploadfile;
    }

    function run()
    {
        try {
            $xml = simplexml_load_file($this->uploadfile);
            unlink($this->uploadfile);
            if (empty($xml)) {
                $this->result = array('error' => 'Ошибка парсера файла!<br>');
                file_put_contents(dirname(__FILE__) . '/error.log', date(DATE_RFC822) . ' - ' . $_FILES['xml-file']['name'] . "\r\n", FILE_APPEND);
                return;
            }
            $this->parse_xml = $this->parseXMLproductup($xml);
        } catch (Exception $e) {
            unlink($this->uploadfile);
            $this->result = array('error' => 'Ошибка парсера файла!<br>' . $e->getMessage());
            file_put_contents(dirname(__FILE__) . '/error.log', date(DATE_RFC822) . ' - ' . $e->getMessage() . "\r\n", FILE_APPEND);
            return;
        }

        foreach ($this->parse_xml as $product) {
            if ($this->updateProduct($product)) {
                $this->count_up++;
            }
        }

        foreach ($this->all_products as $item) {
            if (!isset($this->handled_products[$item->ID])) {
                $this->zeroing($item);
            }
        }
        $this->result = array(
            'success' => 'Данные сохранены!<br>Товаров в файле: ' . count($this->parse_xml) . '<br>из них обновлено: ' . $this->count_up,
            //'unfound' => 'Не найденные в каталоге товары (' . count($this->unhandled_products) . '):<br><pre>' . print_r($this->unhandled_products, true) . '</pre>',
            //'zeroing' => 'Обнуленные в каталоге товары (' . count($this->zeroing_products) . '):<br><pre>' . print_r($this->zeroing_products, true) . '</pre>',
        );
    }

    function zeroing($product)
    {
        if(get_option('var_empty_no_zero') || (get_option('var_hide_no_zero') && preg_match('/(.*:.+?H$)/', $product->post_excerpt))){
            return;
        }
        if($product->post_type == 'product_variation') {
            wc_update_product_stock($product, 0);
            update_post_meta($product->ID, '_stock', 0);
            update_post_meta($product->ID, '_stock_status', wc_clean('outofstock'));
            //wp_set_post_terms( $product->ID, 'outofstock', 'product_visibility', true );
            wc_delete_product_transients($product->ID);
            $this->zeroing_products[] = $product->post_title . ' (' . $product->ID . ')';
        }
    }

    function updateProduct($product)
    {
        try {
            $id = wc_get_product_id_by_sku($product['article']);

            // Поиск по названию
            /*if(empty($id)){
                global $wpdb;
                $res = $wpdb->get_results( "SELECT * FROM `wp_posts` WHERE `post_type` = 'product' AND `post_title` LIKE '%{$wpdb->esc_like($product['article'])}%';" );
                if(count($res) > 0) {
                    $id = $res[0]->ID;
                }
            }*/

            if (!empty($id)) {
                $price = $product['price'];
                $quantity = $product['quantity'];
                $product = wc_get_product($id);
                if ($product->is_type('variation')) {
                    $args = array(
                        'post_type' => 'product_variation',
                        'post_status' => array('private', 'publish'),
                        'numberposts' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'asc',
                        'post_parent' => $product->id,
                    );

                    $variations = get_posts($args);
                    $all_count = 0;
                    $parent_id = 0;
                    foreach ($variations as $var_item) {
                        $var_product = wc_get_product($var_item->ID);
                        if ($var_item->ID == $id) {
                            $all_count += $quantity;
                            wc_update_product_stock($var_product, $quantity);
                            $out_of_stock_status = 'instock';
                            if ($quantity <= 0) {
                                $out_of_stock_status = 'outofstock';
                            }
                            update_post_meta($var_item->ID, '_stock', $quantity);
                            update_post_meta($var_item->ID, '_stock_status', wc_clean($out_of_stock_status));
                            //wp_set_post_terms( $product->id, 'outofstock', 'product_visibility', true );

                            if (!$var_product->is_on_sale()) {
                                update_post_meta($var_item->ID, '_price', $price);
                            }
                            update_post_meta($var_item->ID, '_regular_price', $price);
                            wc_delete_product_transients($var_item->ID);
                        } else {
                            $all_count += $var_product->get_data()['stock_quantity'];
                        }
                        $parent_id = $var_product->get_data()['parent_id'];
                    }

                    $out_of_stock_status = 'instock';
                    if ($all_count <= 0) {
                        $out_of_stock_status = 'outofstock';
                    }
//                    global $wpdb;
//                    $wpdb->query("update `{$wpdb->postmeta}` set meta_value = {$all_count} where post_id = '{$parent_id}' and meta_key = '_stock';");
//                    $wpdb->query("update `{$wpdb->postmeta}` set meta_value = {$out_of_stock_status} where post_id = '{$parent_id}' and meta_key = '_stock_status';");
                    update_post_meta($parent_id, '_stock', $all_count);
                    update_post_meta($parent_id, '_stock_status', wc_clean($out_of_stock_status));
                    wc_delete_product_transients($product->id);
                } else {
                    $this->updateProductPostMeta($product, $quantity, $price);
                }
                $this->handled_products[$id] = $id;
                return true;
            } else {
                $this->unhandled_products[] = $product['article'];
                return false;
            }
        } catch (Exception $e) {
            file_put_contents(dirname(__FILE__) . '/error.log', date(DATE_RFC822) . ' (update) - ' . $e->getMessage() . "\r\n", FILE_APPEND);
            return false;
        }
    }

    function updateProductPostMeta($product, $quantity = 0, $price = 0)
    {
        //wc_update_product_stock($product, $quantity);
        $out_of_stock_status = 'instock';
        if ($quantity <= 0) {
            $out_of_stock_status = 'outofstock';
        }
        update_post_meta($product->id, '_stock', $quantity);
        update_post_meta($product->id, '_stock_status', wc_clean($out_of_stock_status));
        //wp_set_post_terms( $product->id, 'outofstock', 'product_visibility', true );

        if (!$product->is_on_sale()) {
            update_post_meta($product->id, '_price', $price);
        }
        update_post_meta($product->id, '_regular_price', $price);
        wc_delete_product_transients($product->id);
    }

    function parseXMLproductup($xml)
    {
        $catalog = [];
        foreach ($xml->Каталог->Товар as $item) {
            $catalog[$item->attributes()['Идентификатор']->__toString()] = [
                'article' => $item->ЗначениеСвойства->attributes()['Значение']->__toString(),
            ];
        }
        foreach ($xml->ПакетПредложений->Предложение as $item) {
            $index = $item->attributes()['ИдентификаторТовара']->__toString();
            if ($catalog[$index]) {
                $catalog[$index]['price'] = $item->attributes()['Цена']->__toString();
                $catalog[$index]['quantity'] = $item->attributes()['Количество']->__toString();
                $catalog[$index]['quantityinbox'] = $item->attributes()['НормаУпаковки']->__toString();
                $catalog[$index]['unit'] = $item->attributes()['Единица']->__toString();
                $catalog[$index]['currency'] = $item->attributes()['Валюта']->__toString();
            }
        }
        return $catalog;
    }
}