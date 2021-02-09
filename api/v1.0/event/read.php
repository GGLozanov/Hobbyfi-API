<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    require "../init.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $eventId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$id, $_GET);

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($event = $db->getChatroomEvent($userId, $eventId)) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data=>$event
            ));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$eventsNotFound), 404);
        }
    }

    $db->closeConnection();
?>