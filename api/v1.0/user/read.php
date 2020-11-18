<?php
    // Receive request w/ Authorization header -> token
    // id inside token -> query db
    require "../init.php";
    include_once '../config/core.php';
    require "../models/user.php";

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
                Constants::$photoUrl=> $user->getHasImage() ? 
                    'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .'/AuthIO-Service/uploads/' . $user->getId() . '.jpg'
                        : null
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