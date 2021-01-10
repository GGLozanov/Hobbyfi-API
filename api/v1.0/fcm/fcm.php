<?php
    require __DIR__ . '/../../../vendor/autoload.php';

    use Kreait\Firebase\Factory;
    use Kreait\Firebase\Messaging\CloudMessage;

    class FCM {
        private \Kreait\Firebase\Messaging $messaging;

        public function __construct() {
            $factory = (new Factory)->withServiceAccount(
                __DIR__ . '/../keys/hobbyfi-firebase-adminsdk-o1f83-e1d558ffae.json'
            );
            $this->messaging = $factory->createMessaging();
        }

        private function createCloudMessageForTopic(string $topicName, string $notificationType, Model $message) {
            /* @var string $fcmServerKey */

            if(!$message->getId() ||
                !Constants::isValidNotificationType($notificationType)) {
                // if there is no id key in data (which will be used in client ALWAYS for each model),
                // then return null for bad input
                return null;
            }

            // not using str constants here because external API
            $fields = array_filter($message->jsonSerialize(), function($element) {
                return !empty($element);
            });

            $this->encodeToJsonInArrayIfFieldExists($fields, Constants::$tags);
            $this->encodeToJsonInArrayIfFieldExists($fields, Constants::$chatroomIds);
            $this->encodeToJsonInArrayIfFieldExists($fields, Constants::$eventIds);

            $fields[Constants::$type] = $notificationType;

            return CloudMessage::withTarget('topic', $topicName)
                ->withData($fields);
        }

        private function sendMessageToTopic(string $topicName, string $notificationType, Model $message) {
            $message = $this->createCloudMessageForTopic($topicName, $notificationType, $message);

            try {
                $result = $this->messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                return false;
            } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
                return -1;
            }

            return isset($result);
        }

        public function sendBatchedMessageToTopics(array $topics, string $notificationType, Model $message) {
            $messages = array_map(function(string $topic) use($notificationType, $message) {
                return $this->createCloudMessageForTopic((string) $topic, $notificationType, $message);
            }, $topics);

            try {
                $result = $this->messaging->sendAll($messages);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                return false;
            } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
                return -1;
            }

            return isset($result);
        }

        public function sendBatchedMessageToChatroom(array $chatroomIds, string $notificationType, Model $message) {
            return $this->sendBatchedMessageToTopics(
                $chatroomIds,
                $notificationType,
                $message
            );
        }

        public function sendMessageToChatroom(int $chatroomId, string $notificationType, Model $message) {
            return $this->sendMessageToTopic(
                Constants::$chatroomTopicPrefix . $chatroomId,
                $notificationType,
                $message
            );
        }

        private function encodeToJsonInArrayIfFieldExists(array& $fields, string $field) {
            if(isset($fields[$field])) {
                $fields[$field] = json_encode($fields[$field]);
            }
        }
    }
?>