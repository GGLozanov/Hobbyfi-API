<?php
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
        // TODO: Add new method for email and password update seperately eventually
        // this method is called for an update user to get the query for its existing/non-null fields
        // FKs don't take part in updateQuery
        public function getUpdateQuery(string $userPassword = null) {
            $sql = "UPDATE users SET";

            $condStatus = 0b0000; // bitmask for all the possibilities of null vals

            $condStatus |= ($this->email != null) << 3; // 1000
            $condStatus |= ($userPassword != null) << 2; // 0100
            $condStatus |= ($this->username != null) << 1; // 0010
            $condStatus |= $this->description != null; // 0001

            $firstField = (int) log($condStatus, 2) + 1; // base 2 log (with 1 added); finds position of MSB

            $commaCount = substr_count(decbin($condStatus), 1);

            $this->addUpdateFieldToQuery($condStatus & 0b1000, $sql, $commaCount, "email", $this->email, 
                $firstField == 4);
            $this->addUpdateFieldToQuery($condStatus & 0b0100, $sql, $commaCount, "password", $userPassword,
                $firstField == 3);
            $this->addUpdateFieldToQuery($condStatus & 0b0010, $sql, $commaCount, "username", $this->username,
                $firstField == 2);
            $this->addUpdateFieldToQuery($condStatus & 0b0001, $sql, $commaCount, "description", $this->description,
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