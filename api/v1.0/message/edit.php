<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
    
    require "../init.php";

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $message = ConverterUtils::getMessageUpdate($ownerId);

        if(!($imageUrl = $db->isChatroomMessageNotSolelyImage($message->getId()))) {
            APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$disallowedModificationOfImageMessages));
        }

        // if string is returned and not a bool, that means the image url is
        // part of the message being updated; therefore, reappend it prior to update
        if(is_string($imageUrl)) {
            $message->setMessage($message->getMessage() . ' ' . $imageUrl);
        }
        
        if($db->updateChatroomMessage($message, $token)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$messageNotUpdated), 406);
        }
    }

    $db->closeConnection();
?>