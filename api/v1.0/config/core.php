<?php
    // show error reporting
    error_reporting(E_ALL);
    
    // set default time-zone
    date_default_timezone_set('Europe/Sofia');

    if(!function_exists("simple_file_get_contents_with_die_handle")) {
        function simple_file_get_contents_with_die_handle(string $filename) {
            $result = file_get_contents($filename);

            if(!$result) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$internalServerErrorNotConfigured), 500);
            }
            return $result;
        }
    }

    // variables used for jwt
    $publicKey = simple_file_get_contents_with_die_handle("../keys/public.pem"); // might these to separate back-end one day
    $privateKey = simple_file_get_contents_with_die_handle("../keys/private.pem");
    $iss = $_SERVER['SERVER_NAME'];
    $aud = $_SERVER['SERVER_NAME']; // to be probably changed
    $iat = 1356999524;
    $nbf = 1357000000;

    $fbAppId = simple_file_get_contents_with_die_handle('../keys/fb_app_id.txt');
    $fbAppSecret = simple_file_get_contents_with_die_handle('../keys/fb_app_secret.txt');
?>