<?php
    require "../init.php";
    require_once("../utils/image_utils.php");

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId);

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $message = ConverterUtils::getMessageCreate($ownerId, $chatroomId);

        if($message = (array_key_exists(Constants::$image, $_POST) ?
                 $db->createChatroomImageMessage($message,
                     ConverterUtils::getFieldFromRequestBodyWithBase64CheckOrDie(Constants::$image), $token)
                    : $db->createChatroomMessage($message, $token))) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok,
                Constants::$id=>$message->getId(), Constants::$createTime=>$message->getCreateTime()));
        } else {
            APIUtils::handleMultiResultError($message, Constants::$messageNotCreated, Constants::$messageNoChatroom,
                406, 403);
        }
    }

    $db->closeConnection();
?>