<?php

    class RequestUtils {
        public static function performChatroomsReadRequestWithDbSource($chatroomSource = null, $authChatroomSource = null) {
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
                $data = $chatroomSource == null ?
                    $authChatroomSource($id, $page) : $chatroomSource($page);

                if($data || $page) {
                    APIUtils::displayAPIResult(array(
                        Constants::$response=>Constants::$ok,
                        Constants::$data_list=>$data == false ? array() : $data
                    ));
                } else {
                    // TODO: Handle not joined chatroom user going to this endpoint with just token error
                    APIUtils::displayAPIResult(array(Constants::$response=>Constants::$chatroomNotFound), 404);
                }
            }

            $db->closeConnection();
        }
    }
?>
