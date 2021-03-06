<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");

    require "../init.php";

    /** @var $db **/

    $token = APIUtils::getTokenFromHeadersOrDie();
    $eventId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id, $_GET);

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        if($success = $db->deleteChatroomEvent($ownerId, $eventId, $token)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok));
        } else {
            APIUtils::handleMultiResultError($success, Constants::$eventNotDeleted,
                Constants::$eventDeleteNoPermission, 406, 403);
        }
    }

    $db->closeConnection();
?>