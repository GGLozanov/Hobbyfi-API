<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    // Retrieves all the users except the one with the id passed in with the auth token
    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $page = ConverterUtils::getFieldFromRequestBody(Constants::$page, $_GET);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($users = $page == null ? $db->getUser($id) :
                $db->getChatroomUsers($id, $page)) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data_list=>$users
            ));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$userNotFound), 404);
        }
    }

    $db->closeConnection();
?>