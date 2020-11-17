<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    header("Accept: application/json");
    
    require "../init.php";
    require "../config/core.php";
    require "../../../vendor/autoload.php";
    require "../utils/jwt_utils.php";
    require "../utils/api_utils.php";

    if(!array_key_exists('email', $_GET) || 
    !array_key_exists('password', $_GET)) {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$missingDataError), 400);
        return;
    }

    $email = $_GET["email"];
    $password = $_GET["password"]; // password is transmitted as plain-text over client; use TLS/HTTPS in future for securing client-server communication and avoiding MiTM

    if($id = $db->validateUser($email, $password)) {
        // Create token and send it here (without id and other information; just unique username)

        $jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (60 * 60 * 5))); // encodes specific jwt w/ exp time for access token
        $refresh_jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (24 * 60 * 60))); // encode refresh token w/ long expiry

        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok, Constants::$jwt=>$jwt, Constants::$refreshJwt=>$refresh_jwt));
    } else {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$authenticationErrorInvalidCredentials), 401);
    }

    $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)
?>
