<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    // Retrieves all the users except the one with the id passed in with the auth token
    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId, $_GET);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if(($users = $db->getChatroomUsers($id, $chatroomId)) || count($users) == 0) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data_list=>$users
            ));
        } else {
            APIUtils::handleMultiResultError($id, Constants::$userNotFound, Constants::$userNoPermissions,
                403, 404);
        }
    }

    $db->closeConnection();
?>