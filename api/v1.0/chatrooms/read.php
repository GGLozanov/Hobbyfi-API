<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    require "../config/core.php";
    require "../utils/image_utils.php";
    /* @var $db */

    // either returns 1 chatroom or many chatrooms (1 if chatroom id passed as query param)
    $token = APIUtils::getTokenFromHeadersOrDie();
    $page = ConverterUtils::getFieldFromRequestBody(Constants::$page, $_GET);

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($chatroom = ($page == null ?
                $db->getChatroom($id) : $db->getChatrooms($id, $page))) {
            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok,
                ($page == null ? Constants::$data : Constants::$data_list)=>$chatroom
            ));
        } else {
            // TODO: Handle not joined chatroom user going to this endpoint with just token error
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$chatroomNotFound), 404);
        }
    }

    $db->closeConnection();

?>