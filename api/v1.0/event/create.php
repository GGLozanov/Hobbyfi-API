<?php
    require "../init.php";
    require "../utils/image_utils.php";

    /** @var $db **/

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        $event = ConverterUtils::getEventCreate($chatroomId);

        if($event = $db->createChatroomEvent($id, $event, $token)) {

//            $status = ImageUtils::uploadImageBasedOnHasImage($event,
//                Constants::chatroomEventImagesDir($event->getId()), Constants::$events);
            
            $db->closeConnection();

            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>Constants::$ok,
                Constants::$id=>$event->getId(),
                Constants::$startDate=>$event->getStartDate()
            ));
        } else {
            APIUtils::handleMultiResultError($event, Constants::$eventNotCreated,
                Constants::$eventCreateNoPermission, 429, 403);
        }
    }

    $db->closeConnection();
?>