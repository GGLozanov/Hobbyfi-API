<?php
    include_once '../utils/converter_utils.php';

    // show error reporting
    error_reporting(E_ALL);
    
    // set default time-zone
    date_default_timezone_set('Europe/Sofia');

    // variables used for jwt
    $publicKey = ConverterUtils::simpleFileGetContentsWithEnvVarFallbackAndDieHandle(
        "../keys/public.pem", 'public_jwt'); // might these to separate back-end one day
    $privateKey = ConverterUtils::simpleFileGetContentsWithEnvVarFallbackAndDieHandle(
        "../keys/private.pem", 'private_jwt');
    $iss = $_SERVER['SERVER_NAME'];
    $aud = $_SERVER['SERVER_NAME']; // to be probably changed
    $iat = 1356999524;
    $nbf = 1357000000;

    $fbAppId = ConverterUtils::simpleFileGetContentsWithEnvVarFallbackAndDieHandle(
        '../keys/fb_app_id.txt', 'fb_app_id');
    $fbAppSecret = ConverterUtils::simpleFileGetContentsWithEnvVarFallbackAndDieHandle(
        '../keys/fb_app_secret.txt', 'fb_app_secret');
?>