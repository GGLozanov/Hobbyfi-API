<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");

    require "../init.php";

    /** @var $db **/

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId);

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        if($deletedEventIds = $db->deleteOldChatroomEvents($ownerId, $chatroomId, $token)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok, Constants::$data_list=>$deletedEventIds));
        } else {
            APIUtils::handleMultiResultError($deletedEventIds, Constants::$noEventsToDelete, Constants::$eventDeleteNoPermission,
                406, 403);
        }
    }

    $db->closeConnection();
?>