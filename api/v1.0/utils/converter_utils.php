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

        public static function getFieldFromRequestBodyWithCustomPredicateOrDie(string $field, $filterPredicate, array $body = null) {
            $value = ConverterUtils::getFieldFromRequestBodyOrDie($field, $body);

            if($filterPredicate($value)) {
                return $value;
            } else APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidDataError), 400);
            die;
        }

        public static function getFieldFromRequestBodyWithCustomPredicateOrNull(string $field, $filterPredicate, array $body = null) {
            $value = ConverterUtils::getFieldFromRequestBody($field, $body);

            if($filterPredicate($value)) {
                return $value;
            }
            return null;
        }

        public static function getUserCreate() {
            $username = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$username);
            $email = ConverterUtils::getFieldFromRequestBodyWithCustomPredicateOrDie(Constants::$email, function($value) {
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            });
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            // $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $tags = ConverterUtils::getMappedTags(Constants::$tagsCreate);

            return new User(null, $email, $username, $description, false, null, $tags);
        }

        public static function getUserUpdate(int $userId) {
            $email = ConverterUtils::getFieldFromRequestBodyWithCustomPredicateOrNull(Constants::$email, function($value) {
                return filter_var($value, FILTER_VALIDATE_EMAIL);
            });
            $username = ConverterUtils::getFieldFromRequestBody(Constants::$username);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $chatroomId = ConverterUtils::getFieldIntValueFromRequestBodyOrNull(Constants::$chatroomId);
            $tags = ConverterUtils::getMappedTags();

            return new User($userId, $email, $username, $description, false,
                $chatroomId != null ? array($chatroomId) : null, $tags);
        }

        public static function getChatroomCreate(int $ownerId) {
            $name = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $tags = ConverterUtils::getMappedTags(Constants::$tagsCreate);

            return new Chatroom(null, $name, $description, false, $ownerId, null, $tags);
        }

        public static function getChatroomUpdate(int $id, int $ownerId) {
            $name = ConverterUtils::getFieldFromRequestBody(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            $tags = ConverterUtils::getMappedTags();

            // ID is added later on in update query
            return new Chatroom($id, $name, $description, false,
                $ownerId, null, $tags);
        }

        public static function getMessageCreate(int $ownerId, int $chatroomId) {
            $message = ConverterUtils::getFieldFromRequestBody(Constants::$message);

            // chatroom sent id garnered from db method
            return new Message(
                null,
                $message,
                null,
                $chatroomId,
                $ownerId
            );
        }

        // this and getMessageCreate are the same method for now (semantic difference) but will be left
        // if the need arises for it to be changed in the future
        public static function getMessageUpdate(int $ownerId) {
            $id = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id);
            $message = ConverterUtils::getFieldFromRequestBody(Constants::$message);

            return new Message($id, $message, null, null, $ownerId);
        }

        public static function getEventCreate(int $chatroomId) {
            $name = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            // $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $date = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$date);
            $lat = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$lat);
            $long = ConverterUtils::getFieldFromRequestBodyOrDie(Constants::$long);

            return new Event(null, $name, $description, false, null, $date, $lat, $long, $chatroomId);
        }

        // TODO: No support added for chatroom id changing yet but it still exists as an opportunity in the model
        public static function getEventUpdate() {
            $id = ConverterUtils::getFieldIntValueFromRequestBodyOrDie(Constants::$id);
            $name = ConverterUtils::getFieldFromRequestBody(Constants::$name);
            $description = ConverterUtils::getFieldFromRequestBody(Constants::$description);
            // $hasImage = ConverterUtils::getFieldFromRequestBody(Constants::$image) != null;
            $date = ConverterUtils::getFieldFromRequestBody(Constants::$date);
            $lat = ConverterUtils::getFieldFromRequestBody(Constants::$lat);
            $long = ConverterUtils::getFieldFromRequestBody(Constants::$long);
            
            return new Event($id, $name, $description, false, null, $date, $lat, $long, null);
        }

        public static function getFieldIntValueFromRequestBodyOrNull(string $field, array $body = null) {
            if(is_null(($value = ConverterUtils::getFieldFromRequestBody($field, $body)))) {
                return null;
            }

            return intval($value);
        }

        public static function getFieldIntValueFromRequestBodyOrDie(string $field, array $body = null) {
            $value = ConverterUtils::getFieldIntValueFromRequestBodyOrNull($field, $body);
            if(is_null($value)) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$missingDataError), 400);
            }

            return $value;
        }

        public static function getDecodedNoAssocArrayFromRequestBodyOrDie(string $field, array $body = null) {
            $jsonData = ConverterUtils::getFieldFromRequestBodyOrDie($field, $body);

            if(is_array($jsonData)) {
                return $jsonData;
            }

            try {
                return json_decode($jsonData, false, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidDataError), 400);
            }
            return null;
        }

        public static function simpleFileGetContentsWithEnvVarFallbackAndDieHandle(string $fileDir, string $envVarName) {
            $result = file_get_contents($fileDir);

            if(!$result) {
                if(!isset($_ENV[$envVarName])) {
                    APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$internalServerErrorNotConfigured), 500);
                }
                return $_ENV[$envVarName];
            }
            return $result;
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

        public static function getFieldFromRequestBodyWithBase64CheckOrNull(string $field, array $body = null) {
            if(($value = ConverterUtils::getFieldFromRequestBody($field, $body))) {
                return null;
            }

            return ConverterUtils::isBase64($value) ? $value : null;
        }

        public static function getFieldFromRequestBodyWithBase64CheckOrDie(string $field, array $body = null) {
            $value = ConverterUtils::getFieldFromRequestBodyOrDie($field, $body);

            if(!ConverterUtils::isBase64($value)) {
                APIUtils::displayAPIResultAndDie(array(Constants::$response=>Constants::$invalidImageEncodingError), 400);
            }

            return $value;
        }

        public static function isBase64($s) {
            // Check if there are valid base64 characters
            if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) return false;

            // Decode the string in strict mode and check the results
            $decoded = base64_decode($s, true);
            if(false === $decoded) return false;

            // Encode the string again
            // if(base64_encode($decoded) != $s) return false;

            return true;
        }
    }
?>