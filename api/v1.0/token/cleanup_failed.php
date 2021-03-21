<?php
    require "../init.php";

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: DELETE");
    header("Content-Type: application/json; charset=UTF-8");

    /* @var $db */

    function authBasic(?string $username, ?string $password) {
        if(!isset($username) || !isset($password)) {
            return false;
        }

        $serverUsername = ConverterUtils::simpleFileGetContentsWithDieHandle('../keys/socket_server_username.txt');
        $serverPassword = ConverterUtils::simpleFileGetContentsWithDieHandle('../keys/socket_server_password.txt');

        if(strcmp($username, $serverUsername) != 0 || strcmp($password, $serverPassword) != 0) {
            return false;
        }

        return true;
    }

    if (!authBasic($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="Hobbyfi-API"');
        header('HTTP/1.0 401 Unauthorized');
        $db->closeConnection();
        APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$authenticationErrorInvalidCredentials), 401);
    }

    if($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        $res = array();
        parse_str(file_get_contents("php://input"), $res);
    }

    $tokens = ConverterUtils::getDecodedNoAssocArrayFromRequestBodyOrDie(Constants::$deviceTokens, $res ?? null); // brilliant suppression of lint warning

    if($db->removeFailedDeviceTokens($tokens)) {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$deviceTokensDeleteSuccess));
    } else {
        APIUtils::displayAPIResult(array(Constants::$response=>Constants::$deviceTokensDeleteFail));
    }

    $db->closeConnection();