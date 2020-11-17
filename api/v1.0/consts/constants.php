<?php

    class Constants {
        // TODO: Add GET/POST keys & DB constants

        public static string $response = "response";
        public static string $jwt = "jwt";
        public static string $refreshJwt = "refresh_jwt";

        public static string $ok = "Ok";

        public static string $userExists = "User already exists";
        public static string $tagsUploadFailed = "Tags upload failed";

        public static string $facebookAccessGranted = "Access granted";
        public static string $facebookUserCreated = "Facebook user created";

        public static string $facebookAuthUserCreateError = "Missing Facebook access token to create Facebook user";
        public static string $expiredTokenError = "Expired refresh token. Reauthenticate.";
        public static string $invalidTokenError = "Unauthorised access. Invalid token. Reauthenticate.";
        public static string $internalServerError = "Internal server error";
        public static string $noAuthorizationHeaderError = "No Authorization header.";
        public static string $missingTokenInfoError = "Missing token info";
        public static string $defaultTokenExpiredError = "Expired token. Get refresh token.";
        public static string $defaultTokenInvalidError = "Unauthorised access. Invalid token.";
        public static string $missingDataError = "Missing data";
        public static string $noCredentialsForUpdateError = "No credentials for update";
        public static string $authenticationErrorInvalidCredentials = "Invalid credentials";

        public static string $userIdJwtKey = "userId";

        // TODO: Add other models' dirs
        public static string $userProfileImagesDir = "user_pfps";
        // would've extracted these into a generator function if PHP hadn't been so goddamn fucking stupid

        public static string $userNotCreated = "User not created";
        public static string $userNotFound = "User not found";
        public static string $userNotUpdated = "User not updated";
        public static string $userNotDeleted = "User not deleted";
        
        // TODO: Other models CRUD error strings

    }

?>