<?php
if (validateFile()) {
    set_time_limit(0);
    ob_start();
    $dir_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/product-xml-updater/';
    if (!is_dir($dir_path)) {
        mkdir($dir_path, 0777, true);
    }
    $uploadfile = $dir_path . basename($_FILES['xml-file']['name']);
    if (!move_uploaded_file($_FILES['xml-file']['tmp_name'], $uploadfile)) {
        $data = array('error' => 'Ошибка загрузки файла!');
        ob_end_clean();
        echo json_encode($data);
        die;
    }

    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
    require_once('Handler.php');
    if($_POST['isnozeroing_hide'] == 'on') {
        add_option('var_hide_no_zero', 1);
    } else {
        add_option('var_hide_no_zero', 0);
    }
    if($_POST['isnozeroing_empty'] == 'on') {
        add_option('var_empty_no_zero', 1);
    } else {
        add_option('var_empty_no_zero', 0);
    }
    $handler = new Handler($uploadfile);
    $handler->run();

    ob_end_clean();
    echo json_encode($handler->result);
    die;
}

function validateFile()
{
    if (empty($_FILES['xml-file'])) {
        $data = array('error' => 'Выберите файл!');
        echo json_encode($data);
        die;
    } elseif ($_FILES['xml-file']['type'] !== 'text/xml') {
        $data = array('error' => 'Допустимый формат: xml!');
        echo json_encode($data);
        die;
    } else return true;
}