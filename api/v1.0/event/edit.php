<?php
    require "../init.php";

    /** @var $db **/

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $event = ConverterUtils::getEventUpdate();

        APIUtils::evaluateModelEditImageUpload(
            $event,
            $event->getId(),
            Constants::chatroomEventImagesDir($event->getId()),
            Constants::$events,
            $event->isUpdateFormEmpty(),
            false
        );

        if($success = $db->updateChatroomEvent($ownerId, $event)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok), 200);
        } else {
            APIUtils::handleMultiDbResultError($success, Constants::$eventNotUpdated,
                Constants::$eventUpdateNoPermission, 406, 403);
        }
    }

    $db->closeConnection();
?>