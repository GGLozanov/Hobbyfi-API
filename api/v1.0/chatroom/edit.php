<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../utils/image_utils.php";
    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        $chatroom = ConverterUtils::getChatroomUpdate($id);

        // leaking db knowledge for something that should be in updateChatroom() method but w/e for now
        if(!($chatroomId = $db->getOwnerChatroomId($id))) {
            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>Constants::$chatroomNoPermissions
            ), 406);
        }

        $chatroom->setId($chatroomId);

        APIUtils::evaluateModelEditImageUpload(
            $chatroom,
            $chatroomId,
            Constants::chatroomImagesDir($chatroomId),
            Constants::$chatrooms,
            $chatroom->isUpdateFormEmpty()
        );

        if($result = $db->updateChatroom($chatroom)) {
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$chatroomNotUpdated;
            $code = 500;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>