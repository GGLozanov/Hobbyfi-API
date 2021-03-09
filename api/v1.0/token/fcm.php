<?php
    require "../init.php";

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");

    /* @var $db */

    $token = APIUtils::getTokenFromHeadersOrDie();

    use Kreait\Firebase\Factory;
    use Kreait\Firebase\Messaging\RegistrationTokens;
    use \Kreait\Firebase\Messaging\RegistrationToken;

    function validateFCMToken(string $deviceToken, bool $deleting) {
        // TODO: Extract into singleton
        $factory = (new Factory)->withServiceAccount(
            __DIR__ . '/../keys/hobbyfi-firebase-adminsdk-o1f83-e1d558ffae.json'
        );
        $messaging = $factory->createMessaging();

        try {
            $tokenAssoc = $messaging->validateRegistrationTokens(RegistrationTokens::fromValue(RegistrationToken::fromValue($deviceToken)));
            $validCount = count($tokenAssoc['valid']);
            $invalidCount = count($tokenAssoc['invalid']);
            $unknownCount = count($tokenAssoc['unknown']);

            return ($deleting ? ($validCount == 1 && $unknownCount == 0) :
                    (($validCount == 0 && $unknownCount == 1) || $validCount == 1)) && $invalidCount == 0;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            return false;
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            return false;
        }
    }

    if($id = APIUtils::validateAuthorisedRequest($token)) {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $deviceToken = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$token);

            if(!validateFCMToken($deviceToken, false)) {
                $db->closeConnection();
                APIUtils::displayAPIResultAndDie(array(Constants::$invalidFCMToken), 400);
            }

            if($db->uploadDeviceToken($id, $deviceToken)) {
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$deviceTokenUploadSuccess));
            } else {
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$deviceTokenUploadFail), 406);
            }
        } else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            $deviceToken = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$token, $_GET);

            if(!validateFCMToken($deviceToken, true)) {
                $db->closeConnection();
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidFCMToken), 400);
            }

            if($db->deleteDeviceToken($id, $deviceToken)) {
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$deviceTokenDeleteSuccess));
            } else {
                APIUtils::displayAPIResult(array(Constants::$response=>Constants::$deviceTokenDeleteFail), 406);
            }
        }
    }

    $db->closeConnection();