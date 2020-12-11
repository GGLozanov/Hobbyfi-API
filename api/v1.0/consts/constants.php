<?php

    class Constants {
        public static string $id = "id";
        public static string $username = "username";
        public static string $password = "password";
        public static string $email = "email"; 
        public static string $description = "description";
        public static string $tags = "tags";
        public static string $tagsCreate = "tags[]";
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
        public static string $userSentId = "user_sent_id";

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
        public static string $imageUploadFailed = "Image upload failed";

        public static string $userIdJwtKey = "userId";

        public static string $userProfileImagesDir = "user_pfps";
        public static string $data = "data";
        public static string $data_list = "data_list";

        public static function userProfileImagesDir(int $userId) {
          return Constants::$userProfileImagesDir . "/" . $userId . ".jpg";
        }
        public static function chatroomImagesDir(int $chatroomId) {
            return "chatroom_imgs_" . $chatroomId . "/" . $chatroomId . ".jpg";
        }

        // TODO: Have different dir schema for messages
        public static function chatroomMessageImagesPath(int $chatroomId, int $messageId) {
            return Constants::chatroomImagesDir($chatroomId) . '/' . $messageId . '.jpg';

        }
        // would've extracted these into a generator function if PHP hadn't been so goddamn fucking stupid

        public static string $userNotCreated = "User not created";
        public static string $userNotFound = "User not found";
        public static string $userNotUpdated = "User not updated. Username may be taken";
        public static string $userNotDeleted = "User not deleted";

        public static string $chatroomNotCreated = "Chatroom not created. User with this id might already own a chatroom or the chatroom's name might be taken";
        public static string $chatroomNotFound = "Chatroom/Chatrooms not found or user with this id is already in a chatroom and shouldn't be receiving rooms";
        public static string $chatroomNotUpdated = "Chatroom not updated. New name may already be taken";
        public static string $chatroomNoPermissions = "No permissions to update this chatroom";
        public static string $chatroomNotDeleted = "Chatroom not deleted. User with this id may not be the owner of their chatroom";

        public static string $userAlreadyInChatroom = "User already is an owner or part of a chatroom";
        
        // TODO: Other models CRUD error strings
        public static string $userTagsTable = "user_tags";
        public static string $chatroomTagsTable = "chatroom_tags";


        public static string $users = "users";
        public static string $chatrooms = "chatrooms";
        public static string $events = "events";
    }

?>