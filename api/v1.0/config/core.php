<?php
    include_once '../utils/converter_utils.php';

    // show error reporting
    error_reporting(E_ALL);
    
    // set default time-zone
    date_default_timezone_set('Europe/Sofia');

    // variables used for jwt
    $publicKey = ConverterUtils::simpleFileGetContentsWithDieHandle("../keys/public.pem"); // might these to separate back-end one day
    $privateKey = ConverterUtils::simpleFileGetContentsWithDieHandle("../keys/private.pem");
    $iss = $_SERVER['SERVER_NAME'];
    $aud = $_SERVER['SERVER_NAME']; // to be probably changed
    $iat = 1356999524;
    $nbf = 1357000000;

    $fbAppId = ConverterUtils::simpleFileGetContentsWithDieHandle('../keys/fb_app_id.txt');
    $fbAppSecret = ConverterUtils::simpleFileGetContentsWithDieHandle('../keys/fb_app_secret.txt');
?>