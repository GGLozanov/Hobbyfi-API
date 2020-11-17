<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");


    require "../init.php";
    require "../utils/api_utils.php";
    require "../models/user.php";

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($db->deleteUser(
            $userId
        )) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$userNotDeleted;
            $code = 500;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }
?>