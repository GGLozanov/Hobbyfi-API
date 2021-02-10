<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET");
    header("Content-Type: application/json; charset=UTF-8");

    require "../init.php";
    include_once '../config/core.php';
    /* @var $db */
    /* @var $mailer */

    $email = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$email, $_GET);

    if($idAndHash = $db->validateUserByEmail($email)) {
        $id = $idAndHash[Constants::$id];
        $hash = $idAndHash[Constants::$password];

        $formEncodedEmail = urlencode($email);
        $serverPath = Constants::getServerPath();
        $emailToken = JWTUtils::encodeJWTWithPrivateKeySymmetric(JWTUtils::getResetPasswordTokenPayload($email, time() + (60 * 60)), $hash);
        $confirmResetPasswordLink = $serverPath . ("/api/v1.0/user/password_reset.html?email=" . $email . "&token=" . $emailToken);

        $output = '<p>Hello,</p>';
        $output .= '<p>Please click on the following link to reset your password.</p>';
        $output .= '<p>-------------------------------------------------------------</p>';
        $output .= '<p><a href="' . $confirmResetPasswordLink . '" target="_blank">' .
            $confirmResetPasswordLink . '</a></p>';
        $output .= '<p>-------------------------------------------------------------</p>';
        $output .= '<p>Please be sure to copy the entire link into your browser.
                The link will expire after 1 hour for security reasons.</p>';
        $output .= '<p>NOTICE: If you did not request this forgotten password email, no action 
        is needed, your password will not be reset. However, you may want to log into 
        your account and change your security password as someone may have guessed it.</p>';
        $subject = "Password Recovery - Hobbyfi";

        $mailer->IsSMTP();
        $mailer->Port = 587;

        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mailer->Host = 'smtp.gmail.com';
        $mailer->Username = "katapultman150@gmail.com";
        $mailer->Password = file_get_contents("../keys/pwd.txt");
        $mailer->SMTPAuth = true;
        $mailer->Port = 25;
        $mailer->IsHTML(true);
        $mailer->From = "hobbyfisupport@gmail.com";
        $mailer->FromName = "Hobbyfi Support";
        $mailer->Sender = "katapultman150@gmail.com";
        $mailer->Subject = $subject;
        $mailer->Body = $output;

        try {
            $mailer->AddAddress($email);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$emailSentFail), 406);
        }

        try {
            if($mailer->Send()) {
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$emailSentSuccess));
            } else {
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$emailSentFail), 406);
            }
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            APIUtils::displayAPIResult(array(Constants::$response=>Constants::$emailSentFail), 406);
        }
    } else {
        APIUtils::handleMultiResultError($idAndHash, Constants::$facebookUserResetAttempt, Constants::$userEmailNotFound, 406, 404);
    }

    $db->closeConnection();