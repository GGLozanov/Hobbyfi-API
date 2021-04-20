<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");

    require "../init.php";
    require "../config/core.php";
    require_once("../utils/image_utils.php");
/* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id, $_GET);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($db->deleteChatroom($id, $chatroomId, $token)) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$chatroomNotDeleted;
            $code = 500;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>