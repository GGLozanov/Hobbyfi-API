<?php
    require "../init.php";
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        if($events = $db->getChatroomEvents($ownerId)) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data_list=>$events
            ));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$eventsNotFound), 404);
        }
    }

    $db->closeConnection();
?>