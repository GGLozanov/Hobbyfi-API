<?php
    require_once("../models/user.php");
    require_once("../models/chatroom.php");
    require_once("../models/message.php");
    require_once("../models/event.php");
    require_once("../models/chatroom.php");
    require_once("tag_utils.php");

    class ConverterUtils {
        public static function getFieldFromRequestBody(string $field, array $body = null) {
            if($body == null) {
                $body = $_POST;
            }

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
            $tags = ConverterUtils::getMappedTags(Constants::$tagsCreate);

            print_r($tags);
            return new User(null, $email, $username, $description, $hasImage, null, $tags);
        }

        public static function getUserUpdate(int $userId) {
            $email = ConverterUtils::getFieldFromRequestBody(Constants::$email);
            $username = ConverterUtils::getFieldFromRequestBody(Constants::$username);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $chatroomId = ConverterUtils::getFieldIntValueOrNull(Constants::$chatroomId);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = ConverterUtils::getMappedTags();

            return new User($userId, $email, $username, $description, $hasImage,
                $chatroomId != null ? array($chatroomId) : null, $tags);
        }

        public static function getChatroomCreate(int $ownerId) {
            $name = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = ConverterUtils::getMappedTags(Constants::$tagsCreate);

            return new Chatroom(null, $name, $description, $hasImage, $ownerId, null, $tags);
        }

        public static function getChatroomUpdate(int $ownerId) {
            $name = ConverterUtils::getFieldFromRequestBody(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = ConverterUtils::getMappedTags();

            // ID is added later on in update query
            return new Chatroom(null, $name, $description, $hasImage, $ownerId, null, $tags);
        }

        public static function getMessageCreate(int $ownerId, int $chatroomId) {
            $message = ConverterUtils::getFieldFromRequestBody(Constants::$message);

            // chatroom sent id garnered from db method
            return new Message(
                null,
                !is_null($message) ? $message :
                    ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$image),
                null,
                $chatroomId,
                $ownerId
            );
        }

        // this and getMessageCreate are the same method for now (semantic difference) but will be left
        // if the need arises for it to be changed in the future
        public static function getMessageUpdate(int $ownerId) {
            $id = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$id);
            $message = ConverterUtils::getFieldFromRequestBody(Constants::$message);

            return new Message($id, $message, null, null, $ownerId);
        }

        public static function getEventCreate() {
            $name = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $date = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$date);
            $lat = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$lat);
            $long = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$long);

            return new Event(null, $name, $description, $hasImage, null, $date, $lat, $long, null);
        }

        // TODO: No support added for chatroom id changing yet but it still exists as an opportunity in the model
        public static function getEventUpdate() {
            $id = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$id);
            $name = ConverterUtils::getFieldFromRequestBody(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $date = ConverterUtils::getFieldFromRequestBody(Constants::$date);
            $lat = ConverterUtils::getFieldFromRequestBody(Constants::$lat);
            $long = ConverterUtils::getFieldFromRequestBody(Constants::$long);
            
            return new Event($id, $name, $description, $hasImage, null, $date, $lat, $long, null);
        }

        public static function getFieldIntValueOrNull(string $field, array $body = null) {
            $value = ConverterUtils::getFieldFromRequestBody($field, $body);
            return $value == null ? null : intval($value);
        }

        private static function getMappedTags(?string $tagField = null, int $recDepth = 0) {
            if($tagField == null)
                $tagField = Constants::$tags;

            $encodedTags = ConverterUtils::getFieldFromRequestBody($tagField);
            // support both receiving tags in one json array line and in multiple
            $tags = ConverterUtils::extractTagsFromEncoded($encodedTags);
            return empty($tags) && $recDepth < 1 ?
                ConverterUtils::getMappedTags($tagField == Constants::$tagsCreate
                    ? Constants::$tags : Constants::$tagsCreate, $recDepth + 1) : $tags;
        }

        private static function extractTagsFromEncoded(?array $encodedTags) {
            $tags = TagUtils::extractTagsFromJson($encodedTags);
            if(empty($tags)) {
                $tags = TagUtils::extractTagsFromSingleJson($encodedTags);
            }
            return $tags;
        }
    }
?>