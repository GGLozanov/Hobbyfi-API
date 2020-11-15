<?php
    class APIUtils { // might rename this class; improper name
        // helper function designed to set response codes and display a response in a JSON format for interpretation by clients
        public static function displayAPIResult(array $response, $responseCode = 200, string $error = null) {
            if($responseCode != 200)
                http_response_code($responseCode);

            if($error !== null)
                array_push($response, $error);

            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        }

        // returns a user id from decoded jwt if valid jwt;
        // return facebook user id if valid facebook access token;
        // else displays API result (error) and returns false (invalid request)
        public static function validateAuthorisedRequest(string $token, string $expiredTokenError = "Expired token. Get refresh token.", 
            string $invalidTokenError = "Unauthorised access. Invalid token.") {
            require "../utils/jwt_utils.php";


            $decoded = JWTUtils::validateAndDecodeJWT($token);

            if($decoded) {
                if(($decodedAssoc = (array) $decoded) && 
                    array_key_exists('userId', $decodedAssoc) && $decodedAssoc['userId']) // check if the token is one generated from here also has a username field
                        return $decodedAssoc['userId'];
                else {
                    $status = "Missing token info.";
                    $code = 406;
                }
            } else {
                if($decoded == null) { // means the token isn't JWT and try Facebook decoding
                    if($userId = FacebookTokenUtils::validateAccessToken($token)) {
                        // TODO: Fetch other information that's received from Facebook 
                        // (like email, username, description?) and compare/update it
                        return $userId;
                    } else {
                        if($tokenValidity == null) 
                            $status = $invalidTokenError;
                        else
                            $status = $expiredTokenError;        
                    }
                } else
                    $status = $expiredTokenError;

                $code = 401;
            }

            APIUtils::displayAPIResult(array("response"=>$status), $code);
            return false;
        }

        public static function getTokenFromHeaders() {
            $headers = apache_request_headers();

            if(!array_key_exists('Authorization', $headers)) {
                APIUtils::displayAPIResult(array("response"=>"Bad request. No Authorization header."), 400);
                return null;
            }

            return str_replace('Bearer: ', '', $headers['Authorization']);
        }
    }
