<?php
    require "../init.php";
    require "../utils/image_utils.php";

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $message = ConverterUtils::getMessageCreate($ownerId);

        if($id = (!@ImageUtils::validateBase64($message) ?
                $db->createChatroomMessage($message)
                    : $db->createChatroomImageMessage($message))) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok, Constants::$id=>$id));
        } else {
            // TODO: Maybe extract into util function ? ?
            if($id == null) {
                $status = Constants::$messageNotCreated;
                $code = 406; // bad input
            } else {
                // false -> user not in a chatroom
                $status = Constants::$messageNoChatroom;
                $code = 403; // forbidden
            }
            APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
        }
    }

    $db->closeConnection();
?>