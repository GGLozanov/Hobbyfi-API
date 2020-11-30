<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../models/chatroom.php";
    require "../models/tag.php";
    require "../utils/image_utils.php";
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $chatroom = ConverterUtils::getChatroomCreate($ownerId);

        if($id = $db->createChatroom($ownerId, $chatroom)) {
            APIUtils::displayAPIResult(array(
                    Constants::$response=>Constants::$ok
                )
            ); // no need to return chatroom if client already has it; can be read & fetched in read endpoint
        } else {
            if($id == null) {
                $status = Constants::$chatroomNotCreated;
                $code = 406; // bad input
            } else {
                // false -> user already in a chatroom
                $status = Constants::$userAlreadyInChatroom;
                $code = 403; // forbidden
            }
            APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
        }
    }

    $db->closeConnection();
?>