<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */

    $token = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$token, $_GET);
    $email = urldecode(ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$email, $_GET));

    $idAndHash = $db->validateUserByEmail($email);

    // TODO: Not JSON error handling
    APIUtils::handleMultiResultError($idAndHash,
        Constants::$facebookUserDisallowPasswordReset, Constants::$userEmailNotFound,
            406, 404, false, true);

    if($decoded = (array) JWTUtils::validateAndDecodeJWTWithPrivateKeySymmetric($token, $idAndHash[Constants::$password])) {
        $email = $decoded[Constants::$email];
        $id = $idAndHash[Constants::$id];

        $newPassword = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$newPassword);
        if(!is_null($newPassword)) {
            if($db->updateUser(new User($id), password_hash($newPassword, PASSWORD_DEFAULT),
                    null, null, $token)) { // empty placeholder user to trigger only update for password w/ id
                header("Location: " . Constants::getServerPath() . "/static/password_reset_success.html");
                exit();
            } else {
                echo "<br/><p>Failed to reset password!</p>";
            }
            return;
        }
    } else {
        // TODO: Reroute to error page
        APIUtils::handleMultiResultError($decoded, "<h2>Invalid Link</h2> <p>The link is invalid. Check if the link was correctly copied or if it has been already used.</p>",
            "<h2>Link Expired.</h2>
    <p>The link is expired. You are trying to use the expired link which is valid only 1 hour.<br/><br/></p>",
            400, 401, true);
        $db->closeConnection();
    }