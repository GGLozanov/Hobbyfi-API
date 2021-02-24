<?php

    class RequestUtils {
        public static function performChatroomsReadRequestWithDbSource($chatroomSource = null) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET");
            header("Content-Type: application/json; charset=UTF-8");

            require_once("../init.php");
            require_once("../config/core.php");
            require_once("../utils/image_utils.php");
            /* @var $db */

            // either returns 1 chatroom or many chatrooms (1 if chatroom id passed as query param)
            $token = APIUtils::getTokenFromHeadersOrDie();
            $page = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$page, $_GET);

            if($id = APIUtils::validateAuthorisedRequest($token)) {
                $data = $chatroomSource($id, $page);

                if($data || count($data) == 0) {
                    APIUtils::displayAPIResult(array(
                        Constants::$response=>Constants::$ok,
                        Constants::$data_list=>$data
                    ));
                } else {
                    // TODO: Handle not joined chatroom user going to this endpoint with just token error
                    APIUtils::handleMultiResultError($data, Constants::$chatroomNotFound, Constants::$chatroomReadNoPermissions,
                        404, 403);
                }
            }
        }
        
        public static function performMessagesReadRequestWithDbSource($messageIdReq = false) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET");
            header("Content-Type: application/json; charset=UTF-8");
            require "../init.php";
            include_once '../config/core.php';
            /* @var $db */

            $token = APIUtils::getTokenFromHeadersOrDie();

            $query = null;
            $page = null;
            $messageId = null;
            if($messageIdReq) {
                $chatroomId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$chatroomId, $_GET);
                $messageId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$messageId, $_GET);
            } else {
                $chatroomId = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$chatroomId, $_GET);
                $page = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$page, $_GET);

                $query = ConverterUtils::getFieldFromRequestBody(Constants::$query, $_GET);
            }

            if($userId = APIUtils::validateAuthorisedRequest($token)) {
                $messages = $db->getChatroomMessages($userId, $chatroomId, $query, $messageId, $page);

                if(isset($messages)) {
                    APIUtils::displayAPIResult(!$messageIdReq ? array(
                        Constants::$response=>Constants::$ok,
                        Constants::$data_list=>$messages
                    ) : array(Constants::$response=>Constants::$ok,
                        Constants::$data_list=>$messages[0], Constants::$page=>$messages[1]));
                } else {
                    if(is_null($messages)) {
                        $status = !$messageIdReq ? Constants::$messagesNotFound : Constants::$messagePageWithIdNotFound;
                        $code = 404;
                    } else {
                        $status = Constants::$messagesNoPermission;
                        $code = 403;
                    }

                    APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
                }
            }
        }
    }
?>
