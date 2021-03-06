<?php
    require "../init.php";

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $deviceToken = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$token);

            if($db->uploadDeviceToken($id, $deviceToken)) {
                APIUtils::displayAPIResult(array(Constants::$deviceTokenUploadSuccess));
            } else {
                APIUtils::displayAPIResult(array(Constants::$deviceTokenUploadFail), 406);
            }
        } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            $deviceToken = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$token, $_GET);

            if($db->deleteDeviceToken($id, $deviceToken)) {
                APIUtils::displayAPIResult(array(Constants::$deviceTokenDeleteSuccess));
            } else {
                APIUtils::displayAPIResult(array(Constants::$deviceTokenDeleteFail), 406);
            }
        }
    }

    $db->closeConnection();