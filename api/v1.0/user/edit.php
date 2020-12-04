<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($userId = APIUtils::validateAuthorisedRequest($token)) {

        $user = ConverterUtils::getUserUpdate($userId);
        $shouldNotUpdateUser = ($password = ConverterUtils::getFieldFromRequestBody(Constants::$password)) == null &&
            $user->isUpdateFormEmpty();

        if($shouldNotUpdateUser) {
            if($user->getHasImage()) {
                ImageUtils::uploadImageToPath($userId, Constants::$userProfileImagesDir, $_POST[Constants::$image], Constants::$users);
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$ok), 200);
            }

            APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
        }

        if($db->updateUser(
            $user,
            $password != null ? password_hash($password, PASSWORD_DEFAULT) : null)) {

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