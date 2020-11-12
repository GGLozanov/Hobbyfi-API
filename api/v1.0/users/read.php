<?php
    // Retrieves all the users except the one with the id passed in with the auth token
    require "../init.php";
    include_once '../config/core.php';
    require "../utils/api_utils.php";
    require "../models/user.php";
    require "../../vendor/autoload.php";

    if(!$jwt = APIUtils::getJwtFromHeaders()) {
        return;
    }

    if(!array_key_exists('page', $_GET)) {
        APIUtils::displayAPIResult(array("response"=>"Missing data."), 400);
        return;
    }

    // TODO: fetch chatroom id from user model (support Facebook id fetch as well)
    // TODO: pass page number as query param?

    if($decoded = APIUtils::validateAuthorisedRequest($jwt)) {
        $id = $decoded['userId']; // id of auth'd user making the request; used to exempt from DB user query

        if($users = $db->getChatroomUsers($id, $_GET['page'])) {
            APIUtils::displayAPIResult(
                array_reduce($users, function($result, User $user) {
                    $userId = $user->getId();
                    $result["user" . $userId] = array(
                        "id"=>$userId,
                        "username"=>$user->getName(), 
                        "description"=>$user->getDescription(),
                        "email"=>$user->getEmail(), 
                        "photo_url"=>$user->getHasImage() ? 
                            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .'/AuthIO-Service/uploads/user_pfps' . $userId . '.jpg' 
                                : null);
                    return $result;
            }, array())); // mapping twice; FIXME - refactor database to return JSON responses directly instead of model classes?
        } else {
            $status = "Internal server error.";
            $code = 500;
            APIUtils::displayAPIResult(array("response"=>$status, $code));
        }
    }
?>