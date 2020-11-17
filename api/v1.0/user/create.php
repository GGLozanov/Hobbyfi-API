<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../models/user.php";
    require "../models/tag.php";
    require "../../../vendor/autoload.php";
    require "../utils/jwt_utils.php";
    require "../utils/api_utils.php";

    // allow facebook access token to be sent in authorization header and handled here to see whether
    // the user should be allowed to register without password, email, etc.

    if(!array_key_exists('username', $_POST)) { // username is mandatory no matter if FB user or normal user
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$missingDataError), 400);
        return;
    }

    $hasPassword = array_key_exists('password', $_POST);
    $hasEmail = array_key_exists('email', $_POST);
    $isFacebookUser = (!$hasPassword || !$hasEmail); // at least one of the main auth credentials is missing with a facebook user

    if($isFacebookUser && 
            !($token = APIUtils::getTokenFromHeaders())) {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$facebookAuthUserCreateError), 401);
        return;
    }

    $email = null;
    if($hasEmail) {
        $email = $_POST["email"];
    }
    
    $username = $_POST["username"];

    $password = null;
    if(!$isFacebookUser) {
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    }
 
    if($db->userExistsOrPasswordTaken($username, $password)) {
        $status = Constants::$userExists; // user w/ same username or password exists
        $code = 409; // 409 - conflict; resource already exists
    } else {
        $description = null;
        if(array_key_exists('description', $_POST)) {
            $description = $_POST["description"];
        }
    
        // TODO: Extract into util function
        $tags = array();
        if(array_key_exists('tags', $_POST)) {
            $jsonTags = json_decode('{' . implode(',', $_POST["tags"]) . '}', true);
    
            // TODO: Error handling for incorrect info here
            // example JSON structure for tags:
                // tags: [ "tag_name" : { "colour" : "#FFFFFF" }, "tag_name2" : { "colour" : "#FFFFFF" } ]
            foreach($jsonTags as $name => $info) {      
                $tags[] = new Tag($name, $info[0]['colour']);
            }    
        }
    
        $hasImage = array_key_exists('image', $_POST) && ($image = $_POST["image"]) != null;

        if($id = $db->createUser(new User(null, $email, $username, $description, $hasImage, null, $tags), $password)) { // hopefully short-circuit eval works here and doesn't perform a wrong sql query on an empty tag array
            
            // FIXME: Code repetition here
            if($tags) {
                if(!$db->updateUserTags($id, $tags)) {
                    APIUtils::displayAPIResult(array(Constants::$response=>Constants::$tagsUploadFailed), 406);
                    $db->closeConnection();
                    return;
                }
            }

            // if facebook user authenticates here, send the token back but just don't use it and authenticate facebook user client-side
            $jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (60 * 10))); // encodes specific jwt w/ expiry time for access token
            $refresh_jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (24 * 60 * 60))); // encode refresh token w/ long expiry

            if($hasImage) {
                ImageUtils::uploadImageToPath($id, Constants::$userProfileImagesDir, $image);
                // TODO: Handle image upload fail
            }

            APIUtils::displayAPIResult(array(
                Constants::$response=>Constants::$ok, 
                Constants::$jwt=>$isFacebookUser ? Constants::$facebookUserCreated : $jwt, // FIXME: potential security threat with this no password FB user inferrence
                Constants::$refreshJwt=>$isFacebookUser ? Constants::$facebookAccessGranted : $refresh_jwt), 201); // 201 - created; not the best API design... lol
            $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)
            return;
        } else {
            $status = Constants::$userNotCreated;
            $code = 406; // 406 - bad input
        }
    }

    APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
        // output the result in the form of a json encoded response (response<->status & new user id<->last insert id)
        // last inser Id might cause problems in the future and return incorrect ids if multiple queries are occurring at the same time

        // need id for image upload; might need to rework that and have image upload here.
        // id is nonexistent if there's a server error

   $db->closeConnection();
?>
