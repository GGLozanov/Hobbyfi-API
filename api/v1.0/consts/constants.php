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
        public static string $leaveChatroomId = "leave_chatroom_id";
        public static string $chatroomId = "chatroom_id";
        public static string $chatroomIds = "chatroom_ids";
        public static string $photoUrl = "photo_url";
        public static string $hasImage = "has_image";
        public static string $ownerId = "owner_id";
        public static string $eventId = "event_id";
        public static string $eventIds = "event_ids";
        public static string $message = "message";
        public static string $userSentId = "user_sent_id";
        public static string $createTime = "create_time";
        public static string $chatroomSentId = "chatroom_sent_id";
        public static string $date = "date";
        public static string $lat = "latitude";
        public static string $long = "longitude";
        public static string $startDate = "start_date";

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
        public static string $noAuthorizationHeaderError = "Missing Authorization. Reaffirm there's an Authorization header with a valid Bearer token present";
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

        public static function chatroomEventImagesDir(int $eventId) {
            return '/events_imgs_' . $eventId;
        }

        public static function getPhotoUrlForDir(string $dir) {
            return (array_key_exists('HTTPS', $_SERVER) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . ':'
                . $_SERVER['SERVER_PORT'] .'/Hobbyfi-API/uploads/' . $dir;
        }
        // would've extracted these into a generator function if PHP hadn't been so goddamn fucking stupid

        public static string $chatroomTopicPrefix = "chatroom_";
        public static string $type = "type";

        public static string $getId = "getId()";

        public static string $userNotCreated = "User not created";
        public static string $userNotFound = "User/users not found";
        public static string $userNotUpdated = "User not updated. Username may be taken";
        public static string $userNotDeleted = "User not deleted";

        public static string $chatroomNotCreated = "Chatroom not created. User with this id might already own a chatroom or the chatroom's name might be taken";
        public static string $chatroomNotFound = "Chatroom/Chatrooms not found or user with this id is already in a chatroom and shouldn't be receiving rooms";
        public static string $chatroomNotUpdated = "Chatroom not updated. New name may already be taken";
        public static string $chatroomNoPermissions = "Insufficient permissions to update this chatroom";
        public static string $chatroomNotDeleted = "Chatroom not deleted. User with this id may not be the owner of their chatroom";

        public static string $userAlreadyInChatroom = "User already is an owner or part of a chatroom";
        public static string $messageNotCreated = "Message not created. Bad input";
        public static string $messageNoChatroom = "Message not created. User with this id is not in any chatroom";
        public static string $messageNotUpdated = "Message not updated. User with this id may not have the right to update the message";
        public static string $messageNotDeleted = "Message not deleted";
        public static string $messageNotDeletedPermission = "Message not deleted because user with this id does not have the right to delete it";
        public static string $messagesNoPermission = "Couldn't find messages because user with this id does not belong to a chatroom";
        public static string $messagesNotFound = "Couldn't fetch messages. Something's gone wrong";

        public static string $eventNotDeleted = "Event not deleted";
        public static string $eventDeleteNoPermission = "Insufficient permissions to delete this event";
        public static string $eventsNotFound = "Events not found";
        public static string $eventNotCreated = "Event not created. Max limit of 250 events may have been reached";
        public static string $eventNotUpdated = "Event not updated";
        public static string $eventCreateNoPermission = "Insufficient permissions to create this event";

        // TODO: Other models CRUD error strings
        public static string $userTagsTable = "user_tags";
        public static string $chatroomTagsTable = "chatroom_tags";

        public static string $users = "users";
        public static string $chatrooms = "chatrooms";
        public static string $events = "events";
        public static string $messages = "messages";

        // notification types
        public static string $CREATE_MESSAGE_TYPE = "CREATE_MESSAGE";
        public static string $EDIT_MESSAGE_TYPE = "EDIT_MESSAGE";
        public static string $DELETE_MESSAGE_TYPE = "DELETE_MESSAGE";
        public static string $JOIN_USER_TYPE = "JOIN_USER";
        public static string $LEAVE_USER_TYPE = "LEAVE_USER";
        public static string $EDIT_USER_TYPE = "EDIT_USER";
        public static string $DELETE_CHATROOM_TYPE = "DELETE_CHATROOM";
        public static string $EDIT_CHATROOM_TYPE = "EDIT_CHATROOM";
        public static string $CREATE_EVENT_TYPE = "CREATE_EVENT";
        public static string $EDIT_EVENT_TYPE = "EDIT_EVENT";
        public static string $DELETE_EVENT_TYPE = "DELETE_EVENT";

        // very bruh func; would've done it with an array had PHP, again, allowed initialisation with static properties
        public static function isValidNotificationType(string $notificationType) {
            return $notificationType == Constants::$CREATE_MESSAGE_TYPE ||
                $notificationType == Constants::$EDIT_MESSAGE_TYPE ||
                $notificationType == Constants::$DELETE_MESSAGE_TYPE ||
                $notificationType == Constants::$JOIN_USER_TYPE ||
                $notificationType == Constants::$LEAVE_USER_TYPE ||
                $notificationType == Constants::$EDIT_USER_TYPE ||
                $notificationType == Constants::$DELETE_CHATROOM_TYPE ||
                $notificationType == Constants::$EDIT_CHATROOM_TYPE ||
                $notificationType == Constants::$CREATE_EVENT_TYPE ||
                $notificationType == Constants::$EDIT_EVENT_TYPE ||
                $notificationType == Constants::$DELETE_EVENT_TYPE;
        }

        public static function timelineUserJoinMessage($username) {
            return "$username has joined the room!";
        }

        public static function timelineUserLeaveMessage($username) {
            return "$username has left the room!";
        }
    }
?>