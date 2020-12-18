<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");
    
    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    $refresh_jwt = APIUtils::getTokenFromHeadersOrDie();
    // TODO: Check if access token is refresh token or just normal token (will happen when refresh token gets its own payload. . .)

    // exp time for refresh token is one full day from time of issuing
    // if the request is authorised => reissue token
    if($userId = APIUtils::validateAuthorisedRequest($refresh_jwt, Constants::$expiredTokenError, Constants::$invalidTokenError)) {

        // reissue token (new access token); 
        $jwt = JWTUtils::encodeJWT(JWTUtils::getPayload($userId, time() + (8 * 60 * 60))); // new jwt (access token)

        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$ok, Constants::$jwt=>$jwt));
    }

    $db->closeConnection();
?>