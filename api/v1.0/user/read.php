<?php
    // Receive request w/ Authorization header -> token
    // id inside token -> query db
    require "../init.php";
    include_once '../config/core.php';
    require "../utils/api_utils.php";
    require "../models/user.php";
    require "../../vendor/autoload.php";

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($user = $db->getUser($userId)) {
            $status = "ok";

            APIUtils::displayAPIResult(array(
                "response"=>$status, 
                "id"=>$user->getId(), 
                "email"=>$user->getEmail(), 
                "username"=>$user->getName(), 
                "description"=>$user->getDescription(),
                "photo_url"=> $user->getHasImage() ? 
                    'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .'/AuthIO-Service/uploads/' . $user->getId() . '.jpg'
                        : null
                )
            );
            $db->closeConnection();
            return;
        } else {
            $status = "User not found.";
            $code = 404;
            APIUtils::displayAPIResult(array("response"=>$status), $code);
        }
    }

    $db->closeConnection();
?>