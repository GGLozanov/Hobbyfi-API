<?php
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php"; // set up dependency for this script to init php script
    require "../config/core.php";
    require "../models/user.php";
    require "../../../vendor/autoload.php";
    require "../utils/jwt_utils.php";
    require "../utils/api_utils.php";

    // TODO: allow facebook access token to be sent in authorization header and handled here to see whether
    // the user should be allowed to register without password, email, etc.
    
    if(!array_key_exists('username', $_POST)) { // username is mandatory no matter if FB user or normal user
        APIUtils::displayAPIResult(array("response"=>"Missing data."), 400);
        return;
    }

    $email = null;
    if(array_key_exists('email', $_POST)) {
        $email = $_POST["email"];
    }
    
    $username = $_POST["username"];

    $password = null;
    if(!($isFacebookUser = !array_key_exists('password', $_POST))) {
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    }

    $description = null;
    if(array_key_exists('description', $_POST)) {
        $description = $_POST["description"];
    }

    $tags = array();
    if(array_key_exists('tags', $_POST)) {
        $jsonTags = json_decode($_POST['tags']);

        // example JSON structure for tags:
            // tags: [ "tag_name" : { "colour" : "#FFFFFF" }, "tag_name2" : { "colour" : "#FFFFFF" } ]
        foreach($jsonTags as $jsonTag) {
            $tags[] = new Tag($jsonTag[0], $jsonTag[0][0]);
        }    
    }

    $hasImage = array_key_exists('image', $_POST) && ($image = $_POST["image"]) != null;
 
    if($db->userExistsOrPasswordTaken($username, $password)) {
        $status = "exists"; // user w/ same username or password exists
        $code = 204; // resource already exists
    } else {
        if($id = $db->createUser(new User(null, $email, $username, $description, $hasImage, null, $tags), $password) 
            && $tags && $db->updateUserTags($id, $tags)) { // hopefully short-circuit eval works here and doesn't perform a wrong sql query on an empty tag array
            $status = "ok";

            // if facebook user authenticates here, send the token back but just don't use it and authenticate facebook user client-side
            $jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (60 * 10))); // encodes specific jwt w/ expiry time for access token
            $refresh_jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (24 * 60 * 60))); // encode refresh token w/ long expiry

            if($hasImage) {
                ImageUtils::uploadImageToPath($id, "user_pfps", $image);
                // TODO: Handle image upload fail
            }

            APIUtils::displayAPIResult(array(
                "response"=>$status, 
                "jwt"=>$isFacebookUser ? "Facebook user created" : $jwt, // FIXME: potential security threat with this no password FB user inferrence
                "refresh_jwt"=>$isFacebookUser ? "Access Granted" : $refresh_jwt)); // not the best API design... lol
            $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)
            return;
        } else {
            $status = "failed";
            $code = 406; // 406 - bad input
        }
    }

    APIUtils::displayAPIResult(array("response"=>$status), $code);
        // output the result in the form of a json encoded response (response<->status & new user id<->last insert id)
        // last inser Id might cause problems in the future and return incorrect ids if multiple queries are occurring at the same time

        // need id for image upload; might need to rework that and have image upload here.
        // id is nonexistent if there's a server error

   $db->closeConnection();
?>
