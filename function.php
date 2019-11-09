<?php
function create_admin_page()
{
    function add_my_page()
    {
        ?>
        <h1 class="wp-heading-inline">Обновление остатков и цен товаров из XML файла</h1>
        <form method="post" action="<?= plugins_url() . "/product-xml-updater/controller.php" ?>"
              id="xml-form" enctype="multipart/form-data">
            <div id="poststuff">
                <div id="normal-sortables" class="meta-box-sortables">
                    <div id="text-id" class="postbox options-postbox">
                        <div class="inside" id="inside">
                            <label class="lbl-xml" for="xml-file">Выберите файл: </label>
                            <input id="xml-file" name="xml-file" type="file" accept="text/xml" />
                            <p class="description">Допустимый формат: xml</p>
                            <div class="block-button">
                                <input type="button" class="my-button button-action"
                                       onclick="loadXML()" value="Загрузить">
                                <p class="notification"></p>
                            </div>
                        </div>

                        <div class="inside">
                            <label>Не обнулять скрытые вариации <input type="checkbox" name="isnozeroing_hide"
                                    <?= get_option('var_hide_no_zero') ? 'checked': '';?>/></label>
                        </div>
                        <div class="inside">
                            <label>Не обнулять вариации, отсутствующие в файле <input type="checkbox" name="isnozeroing_empty"
                                    <?= get_option('var_empty_no_zero') ? 'checked': '';?>/></label>
                        </div>

                        <div class="log-txt-unfound"></div>
                        <div class="log-txt-zeroing"></div>
                    </div>
                </div>
            </div>
        </form>
        <?php
        wp_enqueue_script('admin-script', plugins_url() . '/product-xml-updater/js/script.js',
            array(),'1.0.0', true);
        wp_enqueue_style('admin-style', plugins_url() . '/product-xml-updater/css/style.css');
    }

    add_action('admin_menu', function () {
        add_submenu_page(
            'woocommerce',
            'Обновление товаров из xml файла',
            'Обновление товаров из xml файла',
            'manage_options',
            'product_xml_updater',
            'add_my_page'
        );
    });
}