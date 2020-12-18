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
        public static string $message = "message";
        public static string $userSentId = "user_sent_id";
        public static string $createTime = "create_time";
        public static string $chatroomSentId = "chatroom_sent_id";

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
        public static string $chatroomImagesDir = "chatroom_imgs_";
        public static string $data = "data";
        public static string $data_list = "data_list";

        public static function userProfileImagesDir(int $userId) {
          return Constants::$userProfileImagesDir . "/" . $userId . ".jpg";
        }

        public static function chatroomImagesDir(int $chatroomId) {
            return Constants::$chatroomImagesDir . $chatroomId;
        }

        // TODO: Have different dir schema for messages
        public static function chatroomMessageImagesDir(int $chatroomId) {
            return Constants::chatroomImagesDir($chatroomId) . '/messages';
        }

        public static function getPhotoUrlForDir(string $dir) {
            return (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . ':'
                . $_SERVER['SERVER_PORT'] .'/Hobbyfi-API/uploads/' . $dir;
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
        public static string $messageNotCreated = "Message not created. Bad input";
        public static string $messageNoChatroom = "Message not created. User with this id is not in any chatroom";
        public static string $messageNotUpdated = "Message not updated. User with this id may not have the right to update the message";
        public static string $messageNotDeleted = "Message not deleted";
        public static string $messageNotDeletedPermission = "Message not deleted because user with this id does not have the right to delete it";
        public static string $messagesNoPermission = "Couldn't find messages because user with this id does not belong to a chatroom";
        public static string $messagesNotFound = "Couldn't fetch messages. Something's gone wrong";

        // TODO: Other models CRUD error strings
        public static string $userTagsTable = "user_tags";
        public static string $chatroomTagsTable = "chatroom_tags";

        public static string $users = "users";
        public static string $chatrooms = "chatrooms";
        public static string $events = "events";
    }

?>