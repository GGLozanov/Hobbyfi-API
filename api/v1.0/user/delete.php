<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");

    require "../init.php";
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($db->deleteUser($userId, $token)) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$userNotDeleted;
            $code = 406;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>