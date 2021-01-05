<?php
    require_once("model.php");
    require_once("id_model.php");
    
    class Message extends Model implements JsonSerializable {
        use \IdModel;

        private ?string $message;
        private ?string $createTime; // ISO string translated to sql timestamp
        private ?int $chatroomSentId;
        private ?int $userSentId;

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

        public function getChatroomSentId() {
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

        public function isUpdateFormEmpty() {
            return $this->message == null && $this->createTime == null
                && $this->chatroomSentId == null && $this->userSentId == null;
        }

        function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE messages SET";

            $updateColumns = array();
            $updateColumns[] = $this->addUpdateFieldToQuery($this->message != null, Constants::$message, $this->message);

            $updateColumns = array_filter($updateColumns);

            $sql .= implode(',', $updateColumns) . " WHERE id = $this->id";

            return $sql;
        }

        public function withPhotoUrlAsMessage() {
            return new Message(
                $this->id,
                Constants::getPhotoUrlForDir(Constants::chatroomMessageImagesDir($this->chatroomSentId)
                    . "/" . $this->id . "jpg"),
                $this->createTime,
                $this->chatroomSentId,
                $this->userSentId
            );
        }

        public function jsonSerialize() {
            return [
                Constants::$id=>$this->id,
                Constants::$message=>$this->message,
                Constants::$createTime=>$this->createTime,
                Constants::$userSentId=>$this->userSentId,
                Constants::$chatroomSentId=>$this->chatroomSentId
            ];
        }

        public function escapeStringProperties(mysqli $conn) {
            $this->setMessage($conn->real_escape_string($this->getMessage()));
        }
    }

?>