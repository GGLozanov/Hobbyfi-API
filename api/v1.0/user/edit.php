<?php
    require "../init.php";
    require "../utils/api_utils.php";
    require "../models/user.php";

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    // FIXME: Code repetition
    $hasEmail = array_key_exists(Constants::$email, $_POST);
    $hasPassword = array_key_exists(Constants::$password, $_POST);
    $hasUsername = array_key_exists(Constants::$username, $_POST);
    $hasDescription = array_key_exists(Constants::$description, $_POST);
    $hasChatroomId = array_key_exists(Constants::$chatroomId, $_POST);
    $hasTags = array_key_exists(Constants::$tags, $_POST);

    if(!$hasEmail && !$hasPassword && !$hasUsername && !$hasDescription && !$hasChatroomId && !$hasTags) {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
        return;
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        if($db->updateUser(
            new User(
                $userId, 
                $hasEmail ? $_POST[Constants::$email] : null, 
                $hasUsername ? $_POST[Constants::$username] : null, 
                $hasDescription ? $_POST[Constants::$description] : null, 
                null,
                $hasChatroomId ? $_POST[Constants::$chatroomId] : null,
                $hasTags ? TagUtils::extractTagsFromPostArray($_POST[Constants::$tags]) : null), 
            $hasPassword ? password_hash($_POST[Constants::$password], PASSWORD_DEFAULT) : null)) {
            
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