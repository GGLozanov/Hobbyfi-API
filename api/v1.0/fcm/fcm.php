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


        // since all the notifications (for now) are meant to be for models
        // data payload is a `Model`; another function can be made for pure arrays if the need arises
        // this is done in order to directly use the specialised version of
        // the JsonSerializable methods each model has
        // (the method in question the same but simultaneously filters out null values)

        // TODO: IF model has tags (chatroom/user), send a boolean or small string to signify update
        // TODO: Then query a new chatroom/user "/tags" endpoint to refetch tags (i.e. user/chatroom tag update)
        // REQUIRES fetch from network; can't sync with db even if pass json as string (plus, it's not scalable)
        // given fcm messages' size limitations
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
                return is_null($element);
            });
            if(isset($fields[Constants::$tags])) {
                $fields[Constants::$tags] = "updated";
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