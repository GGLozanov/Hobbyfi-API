<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId, $_GET);

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        if(($events = $db->getChatroomEvents($ownerId, $chatroomId)) || count($events) == 0) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data_list=>$events
            ));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$eventNotFound), 404);
        }
    }

    $db->closeConnection();
?>