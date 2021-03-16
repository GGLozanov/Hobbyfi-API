<?php

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php";
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId);
    $toggle = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$toggle);

    if($toggle != 0 && $toggle != 1) {
        $db->closeConnection();
        APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidToggleRange), 400);
    }

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($db->toggleUserPushNotificationAllowForChatroom($id, $chatroomId, $toggle)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$pushUserNotificationAllowUpdateSuccess));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$pushUserNotificationAllowUpdateFail), 406);
        }
    }

    $db->closeConnection();