<?php
    require "../init.php";

    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        $event = ConverterUtils::getEventCreate();

        if($db->createChatroomEvent($id, $event)) {

        } else {

        }
    }

    $db->closeConnection();
?>