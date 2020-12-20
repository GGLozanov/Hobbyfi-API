<?php
    require __DIR__ . '/../../../vendor/autoload.php';

    use Kreait\Firebase\Factory;
    use Kreait\Firebase\Messaging\CloudMessage;

    class FCM {
        private \Kreait\Firebase\Messaging $messaging;
        public static string $firebaseUrl =
            "https://fcm.googleapis.com/v1/projects/hobbyfi/messages:send"; // hobbyfi = firebase project id

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
            $fields = json_encode($message);

            // account for null values with slightly iffy regex (send only updated/valid information from JsonSerializable)
            $fields = preg_replace(
                '/,\s*"[^"]+":null|"[^"]+":null,?|,\s*"tags":\[]|"tags":\[]/',
                '',
                $fields
            );

            // replace tag array with "true" to mark needing to fetch them through back-end again
            $fields = preg_replace(
                '/,\s*"tags":\[[^\]]+]/',
                ',"tags": "updated"',
                $fields
            );

            // FIXME: very, very, very, very bad encode/decode for null/empty fields
            // this is actually worse than that one bitmask method I wrote for update query generation
            $fields = json_decode($fields, true);
            $fields[Constants::$type] = $notificationType;

            $message = CloudMessage::withTarget('topic', $topicName)
                ->withData($fields);

            try {
                $result = $this->messaging->send($message);
            } catch (\Kreait\Firebase\Exception\MessagingException $e) {
                print_r($e);
                return false;
            } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
                print_r($e);
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