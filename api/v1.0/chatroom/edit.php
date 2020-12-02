<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../utils/image_utils.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($result = $db->updateChatroom(ConverterUtils::getChatroomUpdate($id))) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            if($result == null) {
                $status = Constants::$chatroomNoPermissions;
                $code = 406;
            } else {
                $status = Constants::$chatroomNotUpdated;
                $code = 500;
            }
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>