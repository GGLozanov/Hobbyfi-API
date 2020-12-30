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

        if($message = (!@ImageUtils::validateBase64($message->getMessage()) ?
                $db->createChatroomMessage($message)
                    : $db->createChatroomImageMessage($message))) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok,
                Constants::$id=>$message->getId(), Constants::$createTime=>$message->getCreateTime()));
        } else {
            // TODO: Maybe extract into util function ? ?
            if($message == false) {
                // false -> user not in a chatroom
                $status = Constants::$messageNoChatroom;
                $code = 403; // forbidden
            } else {
                $status = Constants::$messageNotCreated;
                $code = 406; // bad input
            }
            APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
        }
    }

    $db->closeConnection();
?>