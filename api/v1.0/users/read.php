<?php
    // Retrieves all the users except the one with the id passed in with the auth token
    require "../init.php";
    include_once '../config/core.php';
    require "../models/user.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $page = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$page, $_GET);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($users = $db->getChatroomUsers($id, $page)) {
            APIUtils::displayAPIResult(array(
                Constants::$data_list=>$users
            )); // mapping twice; FIXME - refactor database to return JSON responses directly instead of model classes?
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$internalServerError, 500));
        }
    }

    $db->closeConnection();
?>