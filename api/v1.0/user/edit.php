<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    require "../models/user.php";
    /** @var $db */

    if(!$token = APIUtils::getTokenFromHeaders()) {
        return;
    }

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        // FIXME: Code repetition
        $hasEmail = array_key_exists(Constants::$email, $_POST);
        $hasPassword = array_key_exists(Constants::$password, $_POST);
        $hasUsername = array_key_exists(Constants::$username, $_POST);
        $hasDescription = array_key_exists(Constants::$description, $_POST);
        $hasChatroomId = array_key_exists(Constants::$chatroomId, $_POST);
        $hasTags = array_key_exists(Constants::$tags, $_POST);
        $hasImage = array_key_exists(Constants::$image, $_POST);

        $shouldNotUpdateUser = !$hasEmail && !$hasPassword && !$hasUsername && !$hasDescription && !$hasChatroomId && !$hasTags;

        if($hasImage) {
            ImageUtils::uploadImageToPath($userId, Constants::$userProfileImagesDir, $_POST[Constants::$image]);
            if($shouldNotUpdateUser) { // FIXME: better logic flow here for only image handling
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok), 200);
                return;
            }
        }

        if($shouldNotUpdateUser) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
            return;
        }

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