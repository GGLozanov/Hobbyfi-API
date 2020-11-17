<?php
    require "../init.php";
    require "../utils/api_utils.php";
    require "../models/user.php";

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    $hasEmail = array_key_exists('email', $_POST);
    $hasPassword = array_key_exists('password', $_POST);
    $hasUsername = array_key_exists('username', $_POST);
    $hasDescription = array_key_exists('description', $_POST);
    $hasChatroomId = array_key_exists('chatroom_id', $_POST);

    if(!$hasEmail && !$hasPassword && !$hasUsername && !$hasDescription && !$hasChatroomId) {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
        return;
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($db->updateUser(
            new User(
                $userId, 
                $hasEmail ? $_POST['email'] : null, 
                $hasUsername ? $_POST['username'] : null, 
                $hasDescription ? $_POST['description'] : null, 
                null,
                $hasChatroomId ? $_POST['chatroom_id'] : null)
            , $hasPassword ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null)) {
            
            $status = Constants::$ok;
            $code = 200;
        } else {
            $status = Constants::$userNotUpdated;
            $code = 500;
        }

        APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
    }

    $db->closeConnection();
?>