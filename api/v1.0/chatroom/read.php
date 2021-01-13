<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require_once("../init.php");
    require_once("../config/core.php");
    require_once("../utils/image_utils.php");
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$id, $_GET);

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($chatroom = $db->getChatroom($userId, $chatroomId)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok, Constants::$data=>$chatroom));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$chatroomNotFound), 404);
        }
    }

    $db->closeConnection();
?>