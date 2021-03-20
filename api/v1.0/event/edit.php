<?php
    require "../init.php";

    /** @var $db **/

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $event = ConverterUtils::getEventUpdate();

        if(!($chatroomId = $db->getEventChatroomIdByOwnerIdAndEvent($ownerId, $event))) {
            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>Constants::$eventUpdateNoPermission
            ), 403);
        }

        $event->setChatroomId($chatroomId);
        if($event->isUpdateFormEmpty()) {
            $db->closeConnection();
            APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
        }

        if($success = $db->updateChatroomEvent($event, $token)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok), 200);
        } else {
            APIUtils::handleMultiResultError($success, Constants::$eventNotUpdated,
                Constants::$eventUpdateNoPermission, 406, 403);
        }
    }

    $db->closeConnection();
?>