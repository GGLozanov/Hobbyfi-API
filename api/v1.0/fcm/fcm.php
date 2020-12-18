<?php
    class FCM {
        // todo: cURL methods, topic messaging, etc.
        public string $serverKey;
        
        public function __construct(string $serverKey) {
            $this->serverKey = $serverKey;
        }
        
        public function sendMessageToTopic() {
            
        }

    }
?>