<?php
    // TODO: pass page number as query param
    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $page = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$page, $_GET);

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        $messages = $db->getChatroomMessages($userId, $page);

        if(isset($messages)) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok,
                Constants::$data_list=>$messages));
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