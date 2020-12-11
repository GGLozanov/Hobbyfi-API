<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    /* @var $db */

    $id = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$id, $_GET);

    echo $db->userExists($id);
?>
