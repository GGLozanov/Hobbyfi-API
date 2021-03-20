<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../utils/image_utils.php";
    /** @var $db */

    // allow facebook access token to be sent in authorization header and handled here to see whether
    // the user should be allowed to register without password, email, etc.

    $user = ConverterUtils::getUserCreate();
    if(($password = ConverterUtils::getFieldFromRequestBody(Constants::$password)) != null) {
        $password = password_hash($password, PASSWORD_DEFAULT);
    }

    $isFacebookUser = (!$password || !$user->getEmail()); // at least one of the main auth credentials is missing with a facebook user

    $userId = null;

    if($isFacebookUser) {
        $token = APIUtils::getTokenFromHeadersOrDie();
        if(($userId = FacebookTokenUtils::validateAccessToken($token)) == null || $userId == false) {
            APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$facebookAuthUserCreateError));
        }
        $user->setId($userId);
    }

    if($db->userExistsOrPasswordTaken($user->getName(), $password)) {
        $status = Constants::$userExists; // user w/ same username or password exists
        $code = 409; // 409 - conflict; resource already exists
    } else {
        if($id = $db->createUser($user, $password)) {
            if($tags = $user->getTags()) {
                if(!$db->updateModelTags(Constants::$userTagsTable, Constants::$userId, $id, $tags)) {
                    $db->closeConnection();
                    APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$tagsUploadFailed), 406);
                }
            }
            $user->setId($id);

            // if facebook user authenticates here, send the token back but just don't use it and authenticate facebook user client-side
            $jwt = JWTUtils::encodeJWT(JWTUtils::getUserTokenPayload($id, time() + (8 * 60 * 60))); // encodes specific jwt w/ expiry time for access token
            $refresh_jwt = JWTUtils::encodeJWT(JWTUtils::getUserTokenPayload($id, time() + (24 * 60 * 60))); // encode refresh token w/ long expiry

            // $status = ImageUtils::uploadImageBasedOnHasImage($user, Constants::$userProfileImagesDir, Constants::$users);

            $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)

            APIUtils::displayAPIResultAndDie(array(
                Constants::$response=>Constants::$ok,
                Constants::$jwt=>$isFacebookUser ? Constants::$facebookUserCreated : $jwt,
                Constants::$refreshJwt=>$isFacebookUser ? Constants::$facebookAccessGranted : $refresh_jwt), 201); // 201 - created
        } else {
            $status = Constants::$userNotCreated;
            $code = 406; // 406 - bad input
        }
    }

    APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);

    $db->closeConnection();
?>
