<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php";
    require "../config/core.php";
    require_once("../utils/image_utils.php");

    /**
     * @var $db
     */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $kickUserId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$userId);
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($db->kickUserFromChatroom($id, $chatroomId, $kickUserId, $token)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$kickFailedResponse), 406);
        }
    }

    $db->closeConnection();