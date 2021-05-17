<?php
    require __DIR__ . '/../../../vendor/autoload.php';

    class SocketServerForwarder {
        function forwardRealtimeMessageToSocketServerWithRoomId(Model $model, int $roomId,
                                                                string $type, ?array $idToDeviceToken, string $token) {
            $fields = $this->encodePartiallyMessageDataToJson($model, $type);
            $fields[Constants::$idToToken] = $idToDeviceToken ?? '[]';
            $fields[Constants::$roomId] = $roomId; // some duplication with this data and the model is to be expected, but this is added for total clarity
            return $this->sendForwardingRequestToSocketServer($fields, $token);
        }

        function forwardRealtimeMessageToSecondaryServerWithRoomIdArray(Model $model, array $roomIds,
                                                                        string $type, ?array $roomIdToIdAndDeviceToken, string $token) {
            $fields = $this->encodePartiallyMessageDataToJson($model, $type);
            $fields[Constants::$roomIdToIdAndDeviceToken] = $roomIdToIdAndDeviceToken ?? '[]';
            $fields[Constants::$roomId] = $roomIds;
            return $this->sendForwardingRequestToSocketServer($fields, $token);
        }

        private function sendForwardingRequestToSocketServer(array $fields, string $token) {
            $urlEncodedData = http_build_query($fields);

            $requestUrl = ConverterUtils::simpleFileGetContentsWithEnvVarFallbackAndDieHandle('../keys/socket_server.txt',
                'socket_server_url');
            $curl = curl_init($requestUrl);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_PORT,  parse_url($requestUrl)['scheme'] != "https" ? 3000 : 443); // https/http
            curl_setopt($curl, CURLOPT_POSTFIELDS, $urlEncodedData);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($urlEncodedData),
                    'Authorization: ' . $token,
                    'Expect:'
                )
            );
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_VERBOSE, true);

            $result = curl_exec($curl);
            curl_close($curl);
            return $result;
        }

        private function encodePartiallyMessageDataToJson(Model $model, string $type) {
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

            $fields[Constants::$type] = $type;

            return $fields;
        }

        private function encodeToJsonInArrayIfFieldExists(array& $fields, string $field) {
            if(isset($fields[$field])) {
                $fields[$field] = json_encode($fields[$field]);
            }
        }
    }
