<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    require "../config/core.php";
    require "../utils/image_utils.php";
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

            $status = Constants::$ok;
            if($chatroom->getHasImage()) {
                if(!ImageUtils::uploadImageToPath($id, Constants::$userProfileImagesDir, $_POST[Constants::$image], Constants::$users)) {
                    $status = Constants::$imageUploadFailed;
                }
            }

            $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)

            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>$status
            )); // no need to return chatroom if client already has it; can be read & fetched in read endpoint
        } else {
            if($id == null) {
                $status = Constants::$chatroomNotCreated;
                $code = 406; // bad input
            } else {
                // false -> user already in a chatroom
                $status = Constants::$userAlreadyInChatroom;
                $code = 403; // forbidden
            }
            APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
        }
    }

    $db->closeConnection();
?>