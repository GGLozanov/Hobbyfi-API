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

        if(APIUtils::evaluateModelEditImageUpload(
            $event,
            $event->getId(),
            Constants::chatroomEventImagesDir($event->getId()),
            Constants::$events,
            $event->isUpdateFormEmpty(),
            false
        )) {
            $db->sendNotificationToChatroom(
                $chatroomId,
                Constants::$EDIT_EVENT_TYPE,
                $event
            );
            die;
        }

        if($success = $db->updateChatroomEvent($event)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok), 200);
        } else {
            APIUtils::handleMultiResultError($success, Constants::$eventNotUpdated,
                Constants::$eventUpdateNoPermission, 406, 403);
        }
    }

    $db->closeConnection();
?>