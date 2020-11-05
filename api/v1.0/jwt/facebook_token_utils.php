<?php

use Facebook\Exceptions\FacebookSDKException;

require "../../../vendor/autoload.php";

    class FacebookTokenUtils {
 
        public static function validateAccessToken(string $accessToken) {
            require "../config/core.php";

            $fb = new Facebook\Facebook([
                    'app_id' => $fbAppId,
                    'app_secret' => file_get_contents('../keys/fb_app_secret.txt'),
                    'default_graph_version' => 'v8.0',
                ]
            ); // TODO: Extract into singleton
            $oAuth2Client = $fb->getOAuth2Client();

            $tokenMetadata = $oAuth2Client->debugToken($accessToken);

            if($tokenMetadata == null) {
                return false;
            }

            try {
                return $tokenMetadata->validateAppId($fbAppId) && $tokenMetadata->validateExpiration();
            } catch(\Facebook\Exceptions\FacebookSDKException $e) {
                return false; // invalid if throws exception because token isn't valid?
            }
        }
    }
?>