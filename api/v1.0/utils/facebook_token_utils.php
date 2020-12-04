<?php
    use Facebook\Exceptions\FacebookSDKException;

    class FacebookTokenUtils {

        public static function validateAccessToken(string $accessToken) {
            require "../config/core.php";
            /* @var $fbAppId */
            /* @var $fbAppSecret */

            try {
                $fb = new Facebook\Facebook([
                        'app_id' => $fbAppId,
                        'app_secret' => $fbAppSecret,
                        'default_graph_version' => 'v8.0',
                    ]
                );
            } catch (FacebookSDKException $e) {
                return null;
            } // TODO: Extract into singleton

            $oAuth2Client = $fb->getOAuth2Client();

            try {
                $tokenMetadata = $oAuth2Client->debugToken($accessToken);
                $tokenMetadata->validateAppId($fbAppId);
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                return null; // invalid token (not facebook) if throws exception because token isn't valid?
            }

            try {
                $tokenMetadata->validateExpiration();
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                return false;
            }

            return intval($tokenMetadata->getUserId());
        }
    }
?>