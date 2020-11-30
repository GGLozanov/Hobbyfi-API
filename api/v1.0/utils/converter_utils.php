<?php
    require_once("../models/user.php");
    require_once("../models/chatroom.php");
    require_once("../models/message.php");
    require_once("../models/chatroom.php");
    require_once("tag_utils.php");

    class ConverterUtils {

        public static function getFieldFromRequestBody(string $field, array $body = null) {
            if($body == null) {
                $body = $_POST;
            }
            // TODO: Input validation

            $fieldValue = null;
            if(array_key_exists($field, $body)) {
                $fieldValue = $body[$field];
            }
            return $fieldValue;
        }

        public static function getFieldFromRequestBodyOrDie(string $field, array $body = null) { // should only be used for mandatory endpoints requiring names for resources
            if(($value = ConverterUtils::getFieldFromRequestBody($field, $body)) == null) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$missingDataError), 400);
            }

            return $value;
        }

        public static function getUserCreate() {
            $username = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$username);
            $email = ConverterUtils::getFieldFromRequestBody(Constants::$email);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = TagUtils::extractTagsFromJson(ConverterUtils::getFieldFromRequestBody(Constants::$tags));

            return new User(null, $email, $username, $description, $hasImage, null, $tags);
        }


        public static function getUserUpdate(int $userId) {
            $email = ConverterUtils::getFieldFromRequestBody(Constants::$email);
            $username = ConverterUtils::getFieldFromRequestBody(Constants::$username);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $chatroomId = ConverterUtils::getFieldFromRequestBody(Constants::$chatroomId);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = TagUtils::extractTagsFromJson(ConverterUtils::getFieldFromRequestBody(Constants::$tags));

            return new User($userId, $email, $username, $description, $hasImage, null, $tags);
        }

        public static function getChatroomCreate(int $ownerId) {
            $name = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = TagUtils::extractTagsFromJson(ConverterUtils::getFieldFromRequestBody(Constants::$tags));

            return new Chatroom(null, $name, $description, $hasImage, $ownerId, null, $tags);
        }

        private static function array_equal($a, $b) {
            return (
                is_array($a)
                && is_array($b)
                && count($a) == count($b)
                && array_diff($a, $b) === array_diff($b, $a)
            );
        }
    }
?>