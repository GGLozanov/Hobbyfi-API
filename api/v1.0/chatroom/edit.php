<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../utils/image_utils.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();
    $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        $chatroom = ConverterUtils::getChatroomUpdate($chatroomId, $id);

        if($chatroom->isUpdateFormEmpty() && is_null($chatroom->getTags())) {
            APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
        }

        if($result = $db->updateChatroom($chatroom, $token)) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            APIUtils::handleMultiResultError($result, Constants::$chatroomNoPermissions,
                Constants::$chatroomNotUpdated, 403, 500);
            $db->closeConnection();
            return;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>