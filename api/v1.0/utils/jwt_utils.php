<?php
    require "../../../vendor/autoload.php";
    use \Firebase\JWT\JWT;
    use \Firebase\JWT\ExpiredException;

    // class used to abstract away back-end specifics from boilerplate w/ library
    // userId = given user's id written in their token
    // TODO: Add different payload variable to refresh token to signify refresh token-ness
    class JWTUtils {
        public static function getUserTokenPayload(int $userId, int $time) {
            require "../config/core.php";

            return array(
                "iss" => $iss,
                "aud" => $aud,
                "iat" => $iat,
                "nbf" => $nbf,
                "exp" => $time,
                Constants::$userIdJwtKey => $userId
            ); // token contains the Ids of any resources the user might be associated with/own
        }

        public static function getResetPasswordTokenPayload(string $email, int $time) {
            require "../config/core.php";

            return array(
                "iss" => $iss,
                "aud" => $aud,
                "iat" => $iat,
                "nbf" => $nbf,
                "exp" => $time,
                "jti" => md5($email) . substr(md5(uniqid(rand(),1)),3,10),
            ); // token contains the Ids of any resources the user might be associated with/own
        }
    
        public static function encodeJWT(array $payload) {
            require "../config/core.php";

            return JWT::encode($payload, $privateKey, 'RS256');
        }

        public static function encodeJWTWithPrivateKeySymmetric(array $payload, string $key) {
            require "../config/core.php";

            return JWT::encode($payload, $key);
        }

        public static function validateAndDecodeJWTWithPrivateKeySymmetric(string $jwt, string $key) {
            require "../config/core.php";

            try {
                $decoded = JWT::decode($jwt, $key, array('HS256'));
            } catch(ExpiredException $expired) {
                return false; // false = token is not valid anymore (expired)
            } catch(UnexpectedValueException $e) {
                return null; // null = token is invalid and shouldn't exist
            }

            return $decoded;
        }

        public static function validateAndDecodeJWT(string $jwt) {
            require "../config/core.php";

            try {
                $decoded = JWT::decode($jwt, $publicKey, array('RS256'));
            } catch(ExpiredException $expired) {
                return false; // false = token is not valid anymore (expired)
            } catch(UnexpectedValueException $e) {
                return null; // null = token is invalid and shouldn't exist
            }

            return $decoded; // $decoded = token is valid and not expired
        }
    }
?>