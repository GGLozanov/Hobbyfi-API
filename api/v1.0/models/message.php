<?php

    class Message extends Model {
        use \IdModel;

        private string $message;
        private string $createTime; // ISO string translated to sql timestamp
        private int $chatroomSentId;
        private int $userSentId;        

        function __construct(
                int $id = null, 
                string $message = null, 
                string $createTime = null, 
                int $chatroomSentId = null, int $userSentId = null) {
            $this->id = $id;
            $this->message = $message;
            $this->createTime = $createTime;
            $this->chatroomSentId = $chatroomSentId;
            $this->userSentId = $userSentId; 
        }

        public function getMessage() {
            return $this->message;
        }

        public function setMessage(string $message = null) {
            $this->message = $message;
        }

        public function getCreateTime() {
            return $this->createTime;
        }

        public function setCreateTime(string $createTime = null) {
            $this->createTime = $createTime;
        }

        public function getChatroomSendId() {
            return $this->chatroomSentId;
        }

        public function setChatroomSentId(int $chatroomSentId = null) {
            $this->chatroomSentId = $chatroomSentId;
        }

        public function getUserSentId() {
            return $this->userSentId;
        }

        public function setUserSentId(int $userSentId = null) {
            $this->userSentId = $userSentId;
        }

        function getUpdateQuery(string $userPassword = null) {
            
        }
    }

?>