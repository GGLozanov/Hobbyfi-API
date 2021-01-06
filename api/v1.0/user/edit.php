<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    require "../init.php";
    require "../utils/image_utils.php";
    /** @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    if($userId = APIUtils::validateAuthorisedRequest($token)) {
        $user = ConverterUtils::getUserUpdate($userId);
        APIUtils::evaluateModelEditImageUpload(
            $user,
            $userId,
            Constants::$userProfileImagesDir,
            Constants::$users,
            ($password = ConverterUtils::getFieldFromRequestBody(Constants::$password)) == null &&
                $user->isUpdateFormEmpty()
        );

        if($db->updateUser($user,
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