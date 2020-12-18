<?php
    require_once("../utils/facebook_token_utils.php");

    class APIUtils { // might rename this class; improper name
        // helper function designed to set response codes and display a response in a JSON format for interpretation by clients
        public static function displayAPIResult(array $response, $responseCode = 200, string $error = null) {
            if($responseCode != 200)
                http_response_code($responseCode);

            if($error !== null)
                array_push($response, $error);

            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        }

        public static function displayAPIResultAndDie(array $response, $responseCode = 200, string $error = null) {
            self::displayAPIResult($response, $responseCode, $error);
            die;
        }

        // returns a user id from decoded jwt if valid jwt;
        // return facebook user id if valid facebook access token;
        // else displays API result (error) and returns false (invalid request)
        public static function validateAuthorisedRequest(string $token, string $expiredTokenError = null, 
            string $invalidTokenError = null) {

            $decoded = JWTUtils::validateAndDecodeJWT($token);

            if($expiredTokenError == null)
                $expiredTokenError = Constants::$defaultTokenExpiredError;
            if($invalidTokenError == null)
                $invalidTokenError = Constants::$defaultTokenInvalidError;

            if($decoded) { 
                if(($decodedAssoc = (array) $decoded) && 
                    array_key_exists(Constants::$userIdJwtKey, $decodedAssoc) && $decodedAssoc[Constants::$userIdJwtKey]) // check if the token is one generated from here also has a username field
                        return $decodedAssoc[Constants::$userIdJwtKey];
                else {
                    $status = Constants::$missingTokenInfoError;
                    $code = 406;
                }
            } else {
                if($userId = FacebookTokenUtils::validateAccessToken($token)) {
                    // TODO: Fetch other information that's received from Facebook
                    // (like email, username, description?) and compare/update it
                    return $userId;
                } else {
                    if($userId == false)
                        $status = $expiredTokenError;
                    else
                        $status = $invalidTokenError;
                }

                $code = 401;
            }

            APIUtils::displayAPIResult(array(Constants::$response=>$status), $code);
            return false;
        }

        public static function getTokenFromHeadersOrDie() {
            $headers = apache_request_headers();

            if(!array_key_exists('Authorization', $headers)) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$noAuthorizationHeaderError), 400);
            }

            return str_replace('Bearer: ', '', $headers['Authorization']);
        }

        // Function should ONLY be called for models that use the `TagModel` and `ImageModel` trait
        public static function evaluateModelEditImageUpload($model, int $id, string $dir, string $modelType, bool $shouldNotUpdateModel) {
            if($model->getHasImage()) {
                ImageUtils::uploadImageToPath($id, $dir, $_POST[Constants::$image], $modelType);

                // FIXME: Logic flow
                if($shouldNotUpdateModel) {
                    APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$ok), 200);
                }
            } else if($shouldNotUpdateModel && !($model->getTags())) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$noCredentialsForUpdateError), 400);
            }
        }
    }
