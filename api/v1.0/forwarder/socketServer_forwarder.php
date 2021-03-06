<?php
    require __DIR__ . '/../../../vendor/autoload.php';

    class SocketServerForwarder {
        function forwardRealtimeMessageToSocketServerWithRoomId(Model $model, int $roomId, string $type, array $idToDeviceToken) {
            $fields = $this->encodePartiallyMessageDataToJson($model, $type, $idToDeviceToken);
            $fields[Constants::$roomId] = $roomId; // some duplication with this data and the model is to be expected, but this is added for total clarity
            return $this->sendForwardingRequestToSocketServer($fields);
        }

        function forwardRealtimeMessageToSecondaryServerWithRoomIdArray(Model $model, array $roomIds, string $type, array $roomIdToIdAndDeviceToken) {
            $fields = $this->encodePartiallyMessageDataToJson($model, $type, $roomIdToIdAndDeviceToken);
            $fields[Constants::$roomId] = $roomIds;
            return $this->sendForwardingRequestToSocketServer($fields);
        }

        private function sendForwardingRequestToSocketServer(array $fields) {
            // TODO: change to different hosted URL
            $curl = curl_init('http://localhost:3000/receive_server_message');

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($fields))
            );
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);

            $result = curl_exec($curl);
            curl_close($curl);
            return $result;
        }

        private function encodePartiallyMessageDataToJson(Model $model, string $type, array $idToDeviceToken) {
            if(!Constants::isValidNotificationType($type)) {
                // if there is no id key in data (which will be used in client ALWAYS for each model),
                // then return null for bad input
                return null;
            }

            $fields = array_filter($model->jsonSerialize(), function($element) {
                return !empty($element);
            });

            $this->encodeToJsonInArrayIfFieldExists($fields, Constants::$tags);
            $this->encodeToJsonInArrayIfFieldExists($fields, Constants::$chatroomIds);
            $this->encodeToJsonInArrayIfFieldExists($fields, Constants::$eventIds);

            $fields[Constants::$idToToken] = $idToDeviceToken;
            $fields[Constants::$type] = $type;

            return $fields;
        }

        private function encodeToJsonInArrayIfFieldExists(array& $fields, string $field) {
            if(isset($fields[$field])) {
                $fields[$field] = json_encode($fields[$field]);
            }
        }
    }
