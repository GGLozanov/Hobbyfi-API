<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php";
    require_once("../utils/image_utils.php");
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $leaveChatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrNull(Constants::$leaveChatroomId);
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrNull(Constants::$chatroomId);

    if(isset($leaveChatroomId) && isset($chatroomId)) {
        APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidDataError), 406);
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        $user = ConverterUtils::getUserUpdate($userId);

        $password = ConverterUtils::getFieldFromRequestBody(Constants::$password);

        if($db->updateUser($user,
            $password != null ? password_hash($password, PASSWORD_DEFAULT) : null, $leaveChatroomId, $chatroomId, $token
        )) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$userNotUpdated;
            $code = 406;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>