<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$chatroomId, $_GET);
    $page = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$page, $_GET);

    $query = ConverterUtils::getFieldFromRequestBody(Constants::$query, $_GET);

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        $messages = $db->getChatroomMessages($userId, $chatroomId, $query, $page);

        if(isset($messages)) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                Constants::$data_list=>$messages
            ));
        } else {
            if($messages == false) {
                $status = Constants::$messagesNoPermission;
                $code = 403;
            } else {
                $status = Constants::$messagesNotFound;
                $code = 500;
            }
            APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
        }
    }

    $db->closeConnection();

?>