<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    include_once '../config/core.php';
    require "../models/user.php";
    /** @var $db */

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($user = $db->getUser($userId)) {

            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok, 
                Constants::$id=>$user->getId(), 
                Constants::$email=>$user->getEmail(), 
                Constants::$username=>$user->getName(), 
                Constants::$description=>$user->getDescription(),
                Constants::$chatroomId=>$user->getChatroomId(),
                Constants::$photoUrl=>$user->getHasImage() ?
                    'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .'/AuthIO-Service/uploads/' . $user->getId() . '.jpg'
                        : null,
                Constants::$tags=>$user->getTags()
                )
            );
            $db->closeConnection();
            return;
        } else {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$userNotFound), 404);
        }
    }

    $db->closeConnection();
?>