<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    include_once '../config/core.php';
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($user = $db->getUser($userId)) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data=>$user
            ));
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$userNotFound), 404);
        }
    }

    $db->closeConnection();
?>