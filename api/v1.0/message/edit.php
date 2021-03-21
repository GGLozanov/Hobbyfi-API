<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
    
    require "../init.php";

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $message = ConverterUtils::getMessageUpdate($ownerId);
        
        if($db->updateChatroomMessage($message, $token)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$messageNotUpdated), 406);
        }
    }

    $db->closeConnection();
?>