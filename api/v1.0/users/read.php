<?php
    // Retrieves all the users except the one with the id passed in with the auth token
    require "../init.php";
    include_once '../config/core.php';
    require "../utils/api_utils.php";
    require "../models/user.php";
    require "../../vendor/autoload.php";

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    if(!array_key_exists('page', $_GET)) {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$missingDataError), 400);
        return;
    }

    // TODO: fetch chatroom id from user model (support Facebook id fetch as well)
    // TODO: pass page number as query param?

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($users = $db->getChatroomUsers($id, $_GET['page'])) {
            APIUtils::displayAPIResult(
                array_reduce($users, function($result, User $user) {
                    $userId = $user->getId();
                    $result["user" . $userId] = array(
                        Constants::$id=>$userId,
                        Constants::$username=>$user->getName(), 
                        Constants::$description=>$user->getDescription(),
                        Constants::$email=>$user->getEmail(), 
                        Constants::$photoUrl=>$user->getHasImage() ? 
                            'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .'/AuthIO-Service/uploads/user_pfps' . $userId . '.jpg' 
                                : null);
                    return $result;
            }, array())); // mapping twice; FIXME - refactor database to return JSON responses directly instead of model classes?
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$internalServerError, 500));
        }
    }
?>