<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");


    require "../init.php";
    require "../utils/api_utils.php";
    require "../models/user.php";

    if(!$jwt = APIUtils::getJwtFromHeaders()) {
        return;
    }

    if($decoded = APIUtils::validateAuthorisedRequest($jwt)) {
        if($db->deleteUser(
            $decoded['userId']
        )) {
            $status = "ok";
            $code = 200;
        } else {
            $status = "User not deleted";
            $code = 500;
        }

        APIUtils::displayAPIResult(array("response"=>$status), $code);
    }
?>