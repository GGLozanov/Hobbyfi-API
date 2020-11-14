<?php
    require_once("model.php");
    require_once("id_model.php");
    require_once("expanded_model.php");
    require_once("image_model.php");

    class User extends Model {
        use \IdModel;
        use \ImageModel;
        use \ExpandedModel;
        
        private string $email;

        private int $chatroomId; // FK

        private array $tags;

        public function __construct(
                int $id = null, 
                string $email = null, 
                string $username = null, 
                string $description = null, bool $hasImage = null, 
                int $chatroomId = null, 
                array $tags = null) {     
            $this->id = $id;
            $this->email = $email;
            $this->name = $username;
            $this->description = $description;
            $this->hasImage = $hasImage;

            $this->chatroomId = $chatroomId;
            $this->tags = $tags;
        }

        // TODO: Banal and dumb function; there has to be a better way to do this
        // this method is called for an update user to get the query for its existing/non-null fields
        public function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE users SET";

            $condStatus = 0b00000; // bitmask for all the possibilities of null vals

            $condStatus |= ($this->email != null) << 4; // 10000
            $condStatus |= ($userPassword != null) << 3; // 01000
            $condStatus |= ($this->username != null) << 2; // 00100
            $condStatus |= ($this->description != null) << 1; // 00010
            $condStatus |= $this->chatroomId != null;

            $firstField = (int) log($condStatus, 2) + 1; // base 2 log (with 1 added); finds position of MSB

            $commaCount = substr_count(decbin($condStatus), 1);

            $this->addUpdateFieldToQuery($condStatus & 0b10000, $sql, $commaCount, "email", $this->email, 
                $firstField == 5);
            $this->addUpdateFieldToQuery($condStatus & 0b01000, $sql, $commaCount, "password", $userPassword,
                $firstField == 4);
            $this->addUpdateFieldToQuery($condStatus & 0b00100, $sql, $commaCount, "username", $this->username,
                $firstField == 3);
            $this->addUpdateFieldToQuery($condStatus & 0b00010, $sql, $commaCount, "description", $this->description,
                $firstField == 2);
            $this->addUpdateFieldToQuery($condStatus & 0b00001, $sql, $commaCount, "user_chatroom_id", $this->chatroomId, 
                $firstField == 1);

            $sql .= " WHERE id = $this->id";

            return $sql;
        }

        public function getEmail() {
            return $this->email;
        }

        public function setEmail(string $email = null) {
            $this->email = $email;
        }

        public function getChatroomId() {
            return $this->chatroomId;
        }

        public function setChatroomId(int $chatroomId = null) {
            $this->chatroomId = $chatroomId;
        }

        public function getTags() {
            return $this->tags;
        }

        public function setTags(array $tags = null) {
            $this->tags = $tags;
        }
    }
?>