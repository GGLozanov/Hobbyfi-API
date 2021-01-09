<?php
    require "../init.php";
    require "../utils/image_utils.php";

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $message = ConverterUtils::getMessageCreate($ownerId);

        if($message = (array_key_exists(Constants::$message, $_POST) ?
                $db->createChatroomMessage($message)
                    : $db->createChatroomImageMessage($message))) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok,
                Constants::$id=>$message->getId(), Constants::$createTime=>$message->getCreateTime()));
        } else {
            APIUtils::handleMultiDbResultError($message, Constants::$messageNotCreated, Constants::$messageNoChatroom,
                406, 403);
        }
    }

    $db->closeConnection();
?>