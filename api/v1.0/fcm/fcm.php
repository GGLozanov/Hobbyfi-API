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

        private function sendMessageToTopic(string $topicName, string $notificationType, Model $message) {
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

            if(isset($fields[Constants::$tags])) {
                $fields[Constants::$tags] = json_encode($fields[Constants::$tags]);
            }

            $fields[Constants::$type] = $notificationType;

            $message = CloudMessage::withTarget('topic', $topicName)
                ->withData($fields);

            try {
                $result = $this->messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                return false;
            } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
                return -1;
            }

            return isset($result);
        }

        public function sendMessageToChatroom(int $chatroomId, string $notificationType, Model $message) {
            return $this->sendMessageToTopic(
                Constants::$chatroomTopicPrefix . $chatroomId,
                $notificationType,
                $message
            );
        }
    }
?>