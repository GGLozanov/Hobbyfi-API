<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php";
    require "../config/core.php";
require_once("../utils/image_utils.php");
/* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($ownerId = APIUtils::validateAuthorisedRequest($token)) {
        $chatroom = ConverterUtils::getChatroomCreate($ownerId);

        if($id = $db->createChatroom($chatroom)) {
            if($tags = $chatroom->getTags()) {
                if(!$db->updateModelTags(Constants::$chatroomTagsTable, Constants::$chatroomId, $id, $tags)) {
                    $db->closeConnection();
                    APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$tagsUploadFailed), 406);
                }
            }

//            $status = ImageUtils::uploadImageBasedOnHasImage($chatroom,
//                Constants::chatroomImagesDir($id), Constants::$chatrooms);

            $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)

            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>Constants::$ok,
                Constants::$id=>$id
            )); // no need to return chatroom if client already has it; can be read & fetched in read endpoint
        } else {
            APIUtils::handleMultiResultError($id,
                Constants::$chatroomNotCreated, Constants::$chatroomMaxSize, 429, 429);
        }
    }

    $db->closeConnection();
?>