<?php
    require "../init.php";

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $deviceToken = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$token);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($db->uploadDeviceToken($id, $deviceToken)) {
            APIUtils::displayAPIResult(array(Constants::$deviceTokenUploadSuccess));
        } else {
            APIUtils::displayAPIResult(array(Constants::$deviceTokenUploadFail), 406);
        }
    }

    $db->closeConnection();