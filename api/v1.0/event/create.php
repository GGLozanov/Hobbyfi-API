<?php
    require "../init.php";
    require "../utils/image_utils.php";

    /** @var $db **/

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        $event = ConverterUtils::getEventCreate();

        if($event = $db->createChatroomEvent($id, $event)) {

            $status = ImageUtils::uploadImageBasedOnHasImage($event,
                Constants::chatroomEventImagesDir($event->getId()), Constants::$events);
            
            $db->closeConnection();

            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>$status,
                Constants::$id=>$event->getId(),
                Constants::$startDate=>$event->getStartDate()
            ));
        } else {
            APIUtils::handleMultiDbResultError($event, Constants::$eventNotCreated,
                Constants::$eventCreateNoPermission, 406, 403);
        }
    }

    $db->closeConnection();
?>