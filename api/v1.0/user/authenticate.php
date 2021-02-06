<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
    header("Accept: application/json");
    
    require "../init.php";
    require "../config/core.php";
    /** @var $db */

    $email = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$email, $_GET);
    $password = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$password, $_GET);
    // password is transmitted as plain-text over client; usage of TLS/HTTPS in future (HTTPS already set up)
        // for securing client-server communication and avoiding MiTM

    if($id = $db->validateUser($email, $password)) {
        // Create token and send it here (without id and other information; just unique username)

        $jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (60 * 60 * 6))); // encodes specific jwt w/ exp time for access token
        $refresh_jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($id, time() + (24 * 60 * 60))); // encode refresh token w/ long expiry

        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok, Constants::$jwt=>$jwt, Constants::$refreshJwt=>$refresh_jwt));
    } else {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$authenticationErrorInvalidCredentials), 401);
    }

    $db->closeConnection(); // make sure to close the connection after that (don't allow too many auths in one instance of the web service)
?>
