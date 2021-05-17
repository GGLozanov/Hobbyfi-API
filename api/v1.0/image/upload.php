<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php";
    require_once("../utils/image_utils.php");
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        $type = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$type);
        $modelId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id);
        $image = ConverterUtils::getFieldFromRequestBodyWithBase64CheckOrDie(Constants::$image);

        if($type != Constants::$chatrooms && $type != Constants::$users
            && $type != Constants::$events && $type != Constants::$EDIT_EVENT_TYPE &&
            $type != Constants::$EDIT_CHATROOM_TYPE && $type != Constants::$EDIT_USER_TYPE) {
            APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidTypeError), 400);
        }

        $chatroomId = null;
        if(strcmp($type, Constants::$EDIT_EVENT_TYPE) == 0) {
            $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$chatroomId);
        }

        // kinda retarded, ngl
        switch($type) {
            case Constants::$EDIT_CHATROOM_TYPE:
            case Constants::$chatrooms:
                $uploadPath = ImageUtils::getBucketLocationForChatroom($modelId);
                $uploadType = Constants::$chatrooms;
                $afterEdit = function() use($db, $modelId, $userId, $token) {
                    $db->forwardMessageToSocketServer($modelId,
                        Constants::$EDIT_CHATROOM_TYPE,
                        $db->getChatroom($userId, $modelId),
                        $token
                    );
                };
                break;
            case Constants::$EDIT_USER_TYPE:
            case Constants::$users:
                $uploadPath = ImageUtils::getBucketLocationForUser();
                $uploadType = Constants::$users;
                $afterEdit = function() use($db, $modelId, $token) {
                    if($chatroomIds = $db->getUserChatroomIds($modelId)) {
                        $db->forwardBatchedMessageToSocketServer($chatroomIds,
                            Constants::$EDIT_USER_TYPE,
                            $db->getUser($modelId),
                            $token
                        ); // send batched chatroom update notifications if user is in ANY chatrooms
                    }
                };
                break;
            case Constants::$EDIT_EVENT_TYPE:
            case Constants::$events:
                $uploadPath = ImageUtils::getBucketLocationForChatroomEvent();
                $uploadType = Constants::$events;
                $afterEdit = function() use($db, $modelId, $token, $userId, $chatroomId) {
                    $db->forwardMessageToSocketServer($chatroomId,
                        Constants::$EDIT_EVENT_TYPE,
                        $db->getChatroomEvent($userId, $modelId), $token
                    );
                };
            break;
            default:
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidTypeError), 400);
                die; // clear lint error
        }

        $status = ImageUtils::uploadImageWithResponseReturn($modelId, $uploadPath, $uploadType);

        if(isset($afterEdit) && strstr($type, "EDIT")) {
            $afterEdit();
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status));
        $db->closeConnection();
    }
