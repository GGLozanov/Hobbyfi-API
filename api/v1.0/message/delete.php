<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");

    require "../init.php";

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $messageId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$id, $_GET);

        // TODO: Extract into util method and fix code dup with other delete endpoints... zzzz
        if($success = $db->deleteChatroomMessage($ownerId, $messageId)) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            if($success == false) {
                $status = Constants::$messageNotDeletedPermission;
                $code = 406;
            } else {
                $status = Constants::$messageNotDeleted;
                $code = 500;
            }
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>