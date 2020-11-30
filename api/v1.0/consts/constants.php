<?php

    class Constants {
        public static string $id = "id";
        public static string $username = "username";
        public static string $password = "password";
        public static string $email = "email"; 
        public static string $description = "description";
        public static string $tags = "tags";
        public static string $image = "image";
        public static string $tagName = "tag_name";
        public static string $name = "name";
        public static string $colour = "colour";
        public static string $isFromFacebook = "is_from_facebook";
        public static string $userId = "user_id";
        public static string $chatroomId = "chatroom_id";
        public static string $userChatroomId = "user_chatroom_id";
        public static string $photoUrl = "photo_url";
        public static string $hasImage = "has_image";
        public static string $ownerId = "owner_id";
        public static string $lastEventId = "last_event_id";

        public static string $page = "page";

        public static string $response = "response";
        public static string $jwt = "jwt";
        public static string $refreshJwt = "refresh_jwt";

        public static string $ok = "Ok";

        public static string $userExists = "User already exists";
        public static string $tagsUploadFailed = "Tags upload failed";

        public static string $facebookAccessGranted = "Access granted";
        public static string $facebookUserCreated = "Facebook user created";

        public static string $facebookAuthUserCreateError = "Missing or invalid Facebook access token to create Facebook user";
        public static string $expiredTokenError = "Expired refresh token. Reauthenticate";
        public static string $invalidTokenError = "Unauthorised access. Invalid token. Reauthenticate";
        public static string $internalServerError = "Internal server error";
        public static string $noAuthorizationHeaderError = "No Authorization header";
        public static string $missingTokenInfoError = "Missing token info";
        public static string $defaultTokenExpiredError = "Expired token. Get refresh token";
        public static string $defaultTokenInvalidError = "Unauthorised access. Invalid token";
        public static string $missingDataError = "Missing data";
        public static string $noCredentialsForUpdateError = "No credentials for update";
        public static string $authenticationErrorInvalidCredentials = "Invalid credentials";
        public static $imageUploadFailed = "Image upload failed";

        public static string $userIdJwtKey = "userId";

        // TODO: Add other models' dirs
        public static string $userProfileImagesDir = "user_pfps";
        // would've extracted these into a generator function if PHP hadn't been so goddamn fucking stupid

        public static string $userNotCreated = "User not created";
        public static string $userNotFound = "User not found";
        public static string $userNotUpdated = "User not updated. Username may be taken";
        public static string $userNotDeleted = "User not deleted";

        public static string $chatroomNotCreated = "Chatroom not created";
        public static string $userAlreadyInChatroom = "User already is an owner or part of a chatroom";
        
        // TODO: Other models CRUD error strings
        public static string $userTagsTable = "user_tags";


        public static string $users = "users";
        public static string $chatrooms = "chatrooms";
        public static string $events = "events";
    }

?>