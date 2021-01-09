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
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$eventNotUpdated;
            $code = 406;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>